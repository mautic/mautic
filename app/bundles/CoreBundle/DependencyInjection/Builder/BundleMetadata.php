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
     * @var array<string, mixed>
     */
    private array $metadata = [
        'config'            => [],
        'permissionClasses' => [],
    ];

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;

        if (!isset($this->metadata['permissionClasses'])) {
            $this->metadata['permissionClasses'] = [];
        }

        if (!isset($this->metadata['config'])) {
            $this->metadata['config'] = [];
        }

        $this->directory  = $metadata['directory'];
        $this->namespace  = $metadata['namespace'];
        $this->baseName   = $metadata['bundle'];
        $this->bundleName = $metadata['symfonyBundleName'];
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
