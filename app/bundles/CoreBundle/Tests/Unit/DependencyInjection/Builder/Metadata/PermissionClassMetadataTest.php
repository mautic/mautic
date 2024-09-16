<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder\Metadata;

use Mautic\AssetBundle\Security\Permissions\AssetPermissions;
use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\DependencyInjection\Builder\Metadata\PermissionClassMetadata;
use Mautic\CoreBundle\Security\Permissions\SystemPermissions;
use PHPUnit\Framework\TestCase;

class PermissionClassMetadataTest extends TestCase
{
    public function testPermissionsFound()
    {
        $metadataArray = [
            'isPlugin'          => false,
            'base'              => 'Core',
            'bundle'            => 'CoreBundle',
            'relative'          => 'app/bundles/MauticCoreBundle',
            'directory'         => __DIR__.'/../../../../../',
            'namespace'         => 'Mautic\\CoreBundle',
            'symfonyBundleName' => 'MauticCoreBundle',
            'bundleClass'       => '\\Mautic\\CoreBundle',
        ];

        $metadata                = new BundleMetadata($metadataArray);
        $permissionClassMetadata = new PermissionClassMetadata($metadata);
        $permissionClassMetadata->build();

        $this->assertTrue(isset($metadata->toArray()['permissionClasses'][SystemPermissions::class]));
        $this->assertCount(1, $metadata->toArray()['permissionClasses']);
    }

    public function testCompatibilityWithPermissionServices()
    {
        $metadataArray = [
            'isPlugin'          => false,
            'base'              => 'Asset',
            'bundle'            => 'AssetBundle',
            'relative'          => 'app/bundles/MauticAssetBundle',
            'directory'         => __DIR__.'/../../../../../../AssetBundle',
            'namespace'         => 'Mautic\\AssetBundle',
            'symfonyBundleName' => 'MauticAssetBundle',
            'bundleClass'       => '\\Mautic\\AssetBundle',
        ];

        $metadata                = new BundleMetadata($metadataArray);
        $permissionClassMetadata = new PermissionClassMetadata($metadata);
        $permissionClassMetadata->build();

        $this->assertTrue(isset($metadata->toArray()['permissionClasses'][AssetPermissions::class]));
    }
}
