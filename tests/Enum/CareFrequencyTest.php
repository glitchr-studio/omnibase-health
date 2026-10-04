<?php

namespace Base\Health\Tests\Enum;

use Base\Health\Entity\HomeCareRequest;
use Base\Health\Enum\CareFrequency;
use PHPUnit\Framework\TestCase;

/** How many visits a home care request asks for: calendar days, whatever the hour and the timezone. */
final class CareFrequencyTest extends TestCase
{
    public function testVisitsAreCountedInCalendarDays(): void
    {
        $from = new \DateTimeImmutable('2026-10-12');
        $until = new \DateTimeImmutable('2026-10-21');

        self::assertSame(1, CareFrequency::ONCE->count($from, $until));
        self::assertSame(10, CareFrequency::DAILY->count($from, $until), 'both days included');
        self::assertSame(5, CareFrequency::EVERY_OTHER_DAY->count($from, $until));
        self::assertSame(2, CareFrequency::WEEKLY->count($from, $until));
        self::assertSame(1, CareFrequency::DAILY->count($from, null), 'no end: a single visit');
        self::assertSame(1, CareFrequency::DAILY->count($until, $from), 'an end before the start');
    }

    public function testTheHourAndTheTimezoneOfTheFirstVisitDoNotAddADay(): void
    {
        // The first visit at 8:00 in Paris, the end date read back at midnight UTC.
        $first = new \DateTimeImmutable('2026-10-12 08:00', new \DateTimeZone('Europe/Paris'));
        $until = new \DateTimeImmutable('2026-10-16 00:00', new \DateTimeZone('UTC'));

        self::assertSame(5, CareFrequency::DAILY->count($first, $until));
        self::assertSame(5, CareFrequency::DAILY->count($first->setTime(23, 30), $until));
    }

    public function testThroughTheNightTheClocksChange(): void
    {
        self::assertSame(7, CareFrequency::DAILY->count(new \DateTimeImmutable('2026-10-22'), new \DateTimeImmutable('2026-10-28')), '25 October 2026 has 25 hours and is still one day');
    }

    public function testARequestsVisitCount(): void
    {
        $request = (new HomeCareRequest())->setFrequency('daily')->setStartDate(new \DateTimeImmutable('2026-10-12'))->setEndDate(new \DateTimeImmutable('2026-10-14'));

        self::assertSame(3, $request->getVisitCount());
        self::assertSame('other', $request->setCareType('surgery')->getCareType(), 'an unknown care is "other"');
    }
}
