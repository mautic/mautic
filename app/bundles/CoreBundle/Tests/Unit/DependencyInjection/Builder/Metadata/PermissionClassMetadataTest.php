<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\DependencyInjection\Builder\Metadata\PermissionClassMetadata;
use PHPUnit\Framework\TestCase;

class PermissionClassMetadataTest extends TestCase
{
    /**
     * @var BundleMetadata
     */
    private $metadata;

    protected function setUp()
    {
        $metadataArray = [
            'isPlugin'          => true,
            'base'              => 'Core',
            'bundle'            => 'CoreBundle',
            'relative'          => 'app/bundles/MauticCoreBundle',
            'directory'         => __DIR__.'/../../../../../',
            'namespace'         => 'Mautic\\CoreBundle',
            'symfonyBundleName' => 'MauticCoreBundle',
            'bundleClass'       => '\\Mautic\\CoreBundle',
        ];

        $this->metadata = new BundleMetadata($metadataArray);
    }

    public function testPermissionsFound()
    {
        $permissionClassMetadata = new PermissionClassMetadata($this->metadata);
        $permissionClassMetadata->build();

        $this->assertTrue(isset($this->metadata->toArray()['permissionClasses']['core']));
    }
}
