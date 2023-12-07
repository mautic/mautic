<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

class ResultCacheOptions
{
    private string $namespace;
    private ?int $ttl;
    private ?string $id;

    public function __construct(string $namespace, int $ttl = null, string $id = null)
    {
        $this->namespace = $namespace;
        $this->ttl       = $ttl;
        $this->id        = $id;
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
