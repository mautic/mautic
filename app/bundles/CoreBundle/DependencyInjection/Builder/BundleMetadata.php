<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder;

class BundleMetadata
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $baseName;

    /**
     * @var string
     */
    private $bundleName;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private array $metadata,
    ) {
        $this->metadata['permissionClasses'] ??= [];
        $this->metadata['config'] ??= [];
        $this->directory  = $this->metadata['directory'];
        $this->namespace  = $this->metadata['namespace'];
        $this->baseName   = $this->metadata['bundle'];
        $this->bundleName = $this->metadata['symfonyBundleName'];
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getBaseName(): string
    {
        return $this->baseName;
    }

    public function getBundleName(): string
    {
        return $this->bundleName;
    }

    public function setConfig(array $config): void
    {
        $this->metadata['config'] = $config;
    }

    public function addPermissionClass(string $class): void
    {
        $this->metadata['permissionClasses'][$class] = $class;
    }

    public function toArray(): array
    {
        return $this->metadata;
    }
}
