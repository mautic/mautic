<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Crate;

final class ObjectCrate
{
    public function __construct(
        private readonly string $key,
        private readonly string $name,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
