<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Crate;

final class ObjectCrate
{
    public function __construct(
        private string $key,
        private string $name,
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
