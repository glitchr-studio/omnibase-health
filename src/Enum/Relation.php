<?php

namespace Base\Health\Enum;

enum Relation: string
{
    case CHILD = 'child';
    case PARENT = 'parent';
    case SPOUSE = 'spouse';
    case OTHER = 'other';

    public function label(): string
    {
        return 'relation.'.$this->value;
    }
}
