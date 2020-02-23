<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Finder\Finder;

class PermissionClassMetadata
{
    /**
     * @var BundleMetadata
     */
    private $metadata;

    public function __construct(BundleMetadata $metadata)
    {
        $this->metadata = $metadata;
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
            $permissionClass = sprintf('\\%s\\Security\\Permissions\\%s', $this->metadata->getNamespace(), $className);

            // Skip CorePermissions and AbstractPermissions
            if ('CoreBundle' === $this->metadata->getBaseName() && in_array($className, ['CorePermissions', 'AbstractPermissions'])) {
                continue;
            }

            /** @var AbstractPermissions $permissionInstance */
            $permissionInstance = new $permissionClass([]);
            $permissionName     = $permissionInstance->getName();

            $this->metadata->addPermissionClass($permissionName, $permissionClass);
        }
    }
}
