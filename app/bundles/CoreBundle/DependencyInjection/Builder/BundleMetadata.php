<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Builder;

class BundleMetadata
{
    /**
     * @var array
     */
    private $metadata = [
        'config'            => [],
        'permissionClasses' => [],
    ];

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
