<?php

declare(strict_types=1);

namespace Mautic\FormBundle\DTO;

final readonly class TokenDto
{
    public function __construct(public string $name, public string|int $value)
    {
    }

    public function toString(): string
    {
        return "{{$this->name}={$this->value}}";
    }
}
