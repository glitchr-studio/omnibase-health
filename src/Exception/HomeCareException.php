<?php

namespace Base\Health\Exception;

/** A home care request refused, its reason a translation key of the health domain. */
class HomeCareException extends \RuntimeException
{
    public function __construct(string $key, private readonly array $parameters = [])
    {
        parent::__construct($key);
    }

    public function getKey(): string
    {
        return $this->getMessage();
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
