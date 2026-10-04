<?php

namespace Base\Health\Service;

use App\Entity\User;
use Base\Health\Entity\Dependent;
use Base\Health\Entity\HomeCareRequest;
use Base\Health\Enum\CareFrequency;
use Base\Health\Enum\CareStatus;
use Base\Health\Exception\HomeCareException;
use Base\Office\Booking\Booker;
use Base\Office\Entity\Booking\AppointmentType;
use Base\Office\Entity\Member;
use Base\Office\Entity\Share\Document;
use Base\Office\Enum\Channel;
use Base\Office\Exception\BookingException;
use Base\Office\Repository\Booking\AppointmentTypeRepository;
use Base\Office\Repository\Booking\ServiceAreaRepository;
use Base\Office\Share\Cipher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Care at home, from the request to the agenda. A request is checked
 * against the practice's service areas first: outside all of them it is
 * refused at once, with the reason, rather than left waiting for a call
 * back. Accepted by the practice - who goes, the first visit's day and
 * hour - it becomes a series of appointments (omnibase/office's Booker).
 */
class HomeCare
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ServiceAreaRepository $areas,
        private readonly AppointmentTypeRepository $types,
        private readonly Booker $booker,
        private readonly Cipher $cipher,
    ) {
    }

    /**
     * @param array{street: string, postalCode: string, city: string, phone?: ?string, careType: string, frequency: string, startDate: \DateTimeInterface, endDate?: ?\DateTimeInterface, preferredTime?: ?string, notes?: ?string} $data
     *
     * @throws HomeCareException home_care.error.outside_area, .incomplete, .dates
     */
    public function submit(User $patient, array $data, ?Dependent $dependent = null, ?Document $prescription = null): HomeCareRequest
    {
        $street = trim((string) ($data['street'] ?? ''));
        $postalCode = (string) preg_replace('/\s+/', '', (string) ($data['postalCode'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        if ('' === $street || '' === $postalCode || '' === $city) {
            throw new HomeCareException('home_care.error.incomplete');
        }
        $area = $this->areas->findContaining($postalCode, $city);
        if (null === $area) {
            throw new HomeCareException('home_care.error.outside_area', ['city' => $city]);
        }
        $start = \DateTimeImmutable::createFromInterface($data['startDate'])->setTime(0, 0);
        $end = isset($data['endDate']) && $data['endDate'] ? \DateTimeImmutable::createFromInterface($data['endDate'])->setTime(0, 0) : null;
        if ($start < new \DateTimeImmutable('today') || (null !== $end && $end < $start)) {
            throw new HomeCareException('home_care.error.dates');
        }
        if (null !== $dependent && $dependent->getHolder()?->getId() !== $patient->getId()) {
            $dependent = null;
        }

        $request = (new HomeCareRequest($patient))
            ->setDependent($dependent)
            ->setStreet($street)->setPostalCode($postalCode)->setCity($city)
            ->setPhone($data['phone'] ?? null)
            ->setCareType($data['careType'] ?? 'other')
            ->setFrequency($data['frequency'] ?? CareFrequency::ONCE->value)
            ->setStartDate($start)->setEndDate($end)
            ->setPreferredTime($data['preferredTime'] ?? null)
            ->setPrescription($prescription)
            ->setArea($area);
        $notes = trim((string) ($data['notes'] ?? ''));
        // Sealed, or not stored: the Cipher refuses without a key.
        $request->setNotesCipher('' !== $notes ? $this->cipher->encryptText(mb_substr($notes, 0, 2000)) : null);
        if ($request->getVisitCount() > 120) {
            throw new HomeCareException('home_care.error.dates');
        }

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    /**
     * Accepted: the visits enter the agenda, the first on $first (day and
     * hour), the others at the request's frequency until its end date.
     *
     * @return list<\Base\Office\Entity\Booking\Appointment>
     *
     * @throws HomeCareException|BookingException
     */
    public function accept(HomeCareRequest $request, Member $member, \DateTimeInterface $first, ?User $by = null, ?AppointmentType $type = null): array
    {
        if (!$request->isPending()) {
            throw new HomeCareException('home_care.error.decided');
        }
        $type ??= $this->homeType($member) ?? throw new HomeCareException('home_care.error.no_type', ['member' => (string) $member]);
        $start = \DateTimeImmutable::createFromInterface($first);
        $count = $request->getFrequency()->count($start, $request->getEndDate());

        $appointments = $this->booker->series(
            $type,
            $member,
            $start,
            $count,
            $request->getFrequency()->interval() ?? new \DateInterval('P1D'),
            $request->getPatient(),
            $request->getAddress(),
            ['phone' => $request->getPhone(), 'beneficiary' => $request->getDependent() ? (string) $request->getDependent() : null],
            $by,
            ['home_care' => $request->getId()] + ($request->getDependent() ? ['dependent' => $request->getDependent()->getId()] : []),
        );

        $request->setStatus(CareStatus::ACCEPTED)->setAssignedTo($member)->setSeries($appointments[0]->getSeries());
        $this->entityManager->flush();

        return $appointments;
    }

    public function refuse(HomeCareRequest $request): void
    {
        if ($request->isPending()) {
            $request->setStatus(CareStatus::REFUSED);
            $this->entityManager->flush();
        }
    }

    public function cancel(HomeCareRequest $request): void
    {
        if ($request->isPending()) {
            $request->setStatus(CareStatus::CANCELLED);
            $this->entityManager->flush();
        }
    }

    /** @return list<Member> the members who go to patients: those with a home-visit appointment type */
    public function visitors(): array
    {
        $visitors = [];
        foreach ($this->types->findBy(['active' => true], ['position' => 'ASC']) as $type) {
            if (Channel::HOME === $type->getChannel()) {
                foreach ($type->getMembers() as $member) {
                    if ($member->isActive()) {
                        $visitors[$member->getId()] = $member;
                    }
                }
            }
        }

        return array_values($visitors);
    }

    public function notes(HomeCareRequest $request): ?string
    {
        return $this->cipher->decryptText($request->getNotesCipher());
    }

    /** The member's home-visit appointment type. */
    private function homeType(Member $member): ?AppointmentType
    {
        foreach ($this->types->findForMember($member) as $type) {
            if (Channel::HOME === $type->getChannel()) {
                return $type;
            }
        }

        return null;
    }
}
