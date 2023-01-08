<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadataBuilder;
use Mautic\CoreBundle\Security\Permissions\SystemPermissions;
use MauticPlugin\MauticFocusBundle\Security\Permissions\FocusPermissions;
use PHPUnit\Framework\TestCase;

class BundleMetadataBuilderTest extends TestCase
{
    /**
     * @var array
     */
    private $paths;

    protected function setUp(): void
    {
        // Used in paths_helper
        $root = __DIR__.'/../../../../../../../app';

        /** @var array $paths */
        include __DIR__.'/../../../../../../config/paths_helper.php';

        if (!isset($paths)) {
            throw new \Exception('$paths is not set');
        }

        $this->paths = $paths;
    }

    public function testCoreBundleMetadataLoaded()
    {
        $bundles = ['MauticCoreBundle' => 'Mautic\CoreBundle\MauticCoreBundle'];

        $builder  = new BundleMetadataBuilder($bundles, $this->paths);
        $metadata = $builder->getCoreBundleMetadata();

        $this->assertEquals([], $builder->getPluginMetadata());
        $this->assertTrue(isset($metadata['MauticCoreBundle']));

        $bundleMetadata = $metadata['MauticCoreBundle'];

        $this->assertFalse($bundleMetadata['isPlugin']);
        $this->assertEquals('Core', $bundleMetadata['base']);
        $this->assertEquals('CoreBundle', $bundleMetadata['bundle']);
        $this->assertEquals('MauticCoreBundle', $bundleMetadata['symfonyBundleName']);
        $this->assertEquals('app/bundles/CoreBundle', $bundleMetadata['relative']);
        $this->assertEquals(realpath($this->paths['root']).'/app/bundles/CoreBundle', $bundleMetadata['directory']);
        $this->assertEquals('Mautic\CoreBundle', $bundleMetadata['namespace']);
        $this->assertEquals('Mautic\CoreBundle\MauticCoreBundle', $bundleMetadata['bundleClass']);
        $this->assertTrue(isset($bundleMetadata['permissionClasses']));
        $this->assertTrue(isset($bundleMetadata['permissionClasses'][SystemPermissions::class]));
        $this->assertTrue(isset($bundleMetadata['config']));
        $this->assertTrue(isset($bundleMetadata['config']['routes']));
    }

    public function testPluginMetadataLoaded()
    {
        $bundles = ['MauticFocusBundle' => 'MauticPlugin\MauticFocusBundle\MauticFocusBundle'];

        $builder  = new BundleMetadataBuilder($bundles, $this->paths);
        $metadata = $builder->getPluginMetadata();

        $this->assertEquals([], $builder->getCoreBundleMetadata());
        $this->assertTrue(isset($metadata['MauticFocusBundle']));
        $bundleMetadata = $metadata['MauticFocusBundle'];

        $this->assertTrue($bundleMetadata['isPlugin']);
        $this->assertEquals('MauticFocus', $bundleMetadata['base']);
        $this->assertEquals('MauticFocusBundle', $bundleMetadata['bundle']);
        $this->assertEquals('MauticFocusBundle', $bundleMetadata['symfonyBundleName']);
        $this->assertEquals('plugins/MauticFocusBundle', $bundleMetadata['relative']);
        $this->assertEquals(realpath($this->paths['root']).'/plugins/MauticFocusBundle', $bundleMetadata['directory']);
        $this->assertEquals('MauticPlugin\MauticFocusBundle', $bundleMetadata['namespace']);
        $this->assertEquals('MauticPlugin\MauticFocusBundle\MauticFocusBundle', $bundleMetadata['bundleClass']);
        $this->assertTrue(isset($bundleMetadata['permissionClasses']));
        $this->assertTrue(isset($bundleMetadata['permissionClasses'][FocusPermissions::class]));
        $this->assertTrue(isset($bundleMetadata['config']));
        $this->assertTrue(isset($bundleMetadata['config']['routes']));
    }

    public function testSymfonyBundleIgnored()
    {
        $bundles = ['FooBarBundle' => 'Foo\Bar\BarBundle'];

        $builder = new BundleMetadataBuilder($bundles, $this->paths);
        $this->assertEquals([], $builder->getCoreBundleMetadata());
        $this->assertEquals([], $builder->getPluginMetadata());
    }
}
