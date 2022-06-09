<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Crate;

final class ObjectCrate
{
    private string $key;
    private string $name;

    public function __construct(string $key, string $name)
    {
        $this->key  = $key;
        $this->name = $name;
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
