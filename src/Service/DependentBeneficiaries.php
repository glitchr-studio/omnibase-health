<?php

namespace Base\Health\Service;

use Base\Health\Repository\DependentRepository;
use Base\Office\Booking\BeneficiaryProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** A patient books for themselves or for one of their dependants: a child, a parent they care for. */
final class DependentBeneficiaries implements BeneficiaryProviderInterface
{
    public function __construct(private readonly DependentRepository $dependents, private readonly TranslatorInterface $translator)
    {
    }

    public function beneficiaries(object $user): array
    {
        $choices = [];
        foreach ($this->dependents->findForHolder($user) as $dependent) {
            $choices['dependent-'.$dependent->getId()] = [
                'label' => sprintf('%s (%s)', $dependent, $this->translator->trans($dependent->getRelation()->label(), [], 'health')),
                'meta' => ['dependent' => $dependent->getId()],
            ];
        }

        return $choices;
    }
}
