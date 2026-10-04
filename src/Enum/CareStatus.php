<?php

namespace Base\Health\Enum;

enum CareStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return 'care_status.'.$this->value;
    }
}
