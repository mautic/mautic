<?php

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Finder\Finder;

/**
 * This is an temporary necessity until https://github.com/mautic/mautic/pull/7312 is merged and permission classes are
 * converted to services.
 */
class PermissionClassMetadata
{
    public function __construct(
        private BundleMetadata $metadata
    ) {
    }

    public function build(): void
    {
        $directory = $this->metadata->getDirectory();
        if (!file_exists($directory.'/Security/Permissions')) {
            return;
        }

        $finder = Finder::create()
            ->name('*Permissions.php')
            ->in($directory.'/Security/Permissions');

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $className       = basename($file->getFilename(), '.php');
            $permissionClass = sprintf('%s\\Security\\Permissions\\%s', $this->metadata->getNamespace(), $className);

            // Required because https://github.com/mautic/mautic/pull/7312 introduces permission DI and thus classes cannot be instantiated here
            $reflectionClass = new \ReflectionClass($permissionClass);
            if ($reflectionClass->isAbstract()) {
                // Skip abstract classes
                continue;
            }

            /** @var AbstractPermissions $permissionInstance */
            $permissionInstance = $reflectionClass->newInstanceWithoutConstructor();
            if (!$permissionInstance instanceof AbstractPermissions) {
                // Not a permission class
                continue;
            }

            $this->metadata->addPermissionClass($permissionClass);
        }
    }
}
