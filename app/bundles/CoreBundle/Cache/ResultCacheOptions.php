<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

class ResultCacheOptions
{
    public function __construct(private string $namespace, private ?int $ttl = null, private ?string $id = null)
    {
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
