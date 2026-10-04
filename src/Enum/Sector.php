<?php

namespace Base\Health\Enum;

/** A practitioner's agreement with the health insurance (conventionnement). */
enum Sector: string
{
    case SECTOR_1 = 'sector_1';
    case SECTOR_2 = 'sector_2';
    case OPTAM = 'optam';
    case NOT_CONTRACTED = 'not_contracted';
    /** Professions with a single agreement (nurses, physiotherapists, midwives): "conventionné". */
    case CONTRACTED = 'contracted';

    public function label(): string
    {
        return 'sector.'.$this->value;
    }
}
