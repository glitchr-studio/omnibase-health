<?php

namespace Base\Health\Enum;

enum CareFrequency: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case EVERY_OTHER_DAY = 'every_other_day';
    case WEEKLY = 'weekly';

    public function label(): string
    {
        return 'frequency.'.$this->value;
    }

    /** The interval between two visits; null: a single visit. */
    public function interval(): ?\DateInterval
    {
        return match ($this) {
            self::ONCE => null,
            self::DAILY => new \DateInterval('P1D'),
            self::EVERY_OTHER_DAY => new \DateInterval('P2D'),
            self::WEEKLY => new \DateInterval('P7D'),
        };
    }

    /** How many visits between two calendar days, both included: the days as written, whatever the hour and the timezone. */
    public function count(\DateTimeInterface $from, ?\DateTimeInterface $until): int
    {
        $interval = $this->interval();
        if (null === $interval || null === $until || $until->format('Y-m-d') < $from->format('Y-m-d')) {
            return 1;
        }
        $utc = new \DateTimeZone('UTC');
        $days = (int) (new \DateTimeImmutable($from->format('Y-m-d'), $utc))->diff(new \DateTimeImmutable($until->format('Y-m-d'), $utc))->days;

        return intdiv($days, (int) $interval->d) + 1;
    }
}
