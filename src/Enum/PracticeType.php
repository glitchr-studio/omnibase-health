<?php

namespace Base\Health\Enum;

/** What kind of practice the site is for; the pages put forward what suits it. */
enum PracticeType: string
{
    case MSP = 'msp';
    case HEALTH_CENTRE = 'health_centre';
    case MEDICAL = 'medical';
    case NURSING = 'nursing';
    case PHYSIOTHERAPY = 'physiotherapy';
    case MIDWIFERY = 'midwifery';
    case PARAMEDICAL = 'paramedical';

    public function label(): string
    {
        return 'practice_type.'.$this->value;
    }

    /** A practice that mostly goes to its patients: home care comes first on its pages. */
    public function isHomeCareFirst(): bool
    {
        return self::NURSING === $this;
    }

    /** schema.org's type for the JSON-LD. */
    public function schemaType(): string
    {
        return match ($this) {
            self::MEDICAL => 'Physician',
            self::PHYSIOTHERAPY => 'Physiotherapy',
            default => 'MedicalClinic',
        };
    }
}
