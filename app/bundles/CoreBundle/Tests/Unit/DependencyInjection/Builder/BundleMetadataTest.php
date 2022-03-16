<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use PHPUnit\Framework\TestCase;

class BundleMetadataTest extends TestCase
{
    public function testGetters()
    {
        $metadataArray = [
            'isPlugin'          => true,
            'base'              => 'Core',
            'bundle'            => 'CoreBundle',
            'relative'          => 'app/bundles/MauticCoreBundle',
            'directory'         => '/var/www/app/bundles/MauticCoreBundle',
            'namespace'         => 'Mautic\\CoreBundle',
            'symfonyBundleName' => 'MauticCoreBundle',
            'bundleClass'       => '\\Mautic\\CoreBundle',
        ];

        $metadata = new BundleMetadata($metadataArray);
        $this->assertSame($metadataArray['directory'], $metadata->getDirectory());
        $this->assertSame($metadataArray['namespace'], $metadata->getNamespace());
        $this->assertSame($metadataArray['bundle'], $metadata->getBaseName());
        $this->assertSame($metadataArray['symfonyBundleName'], $metadata->getBundleName());

        $metadata->setConfig(['foo' => 'bar']);
        $metadata->addPermissionClass('\Foo\Bar');

        $metadataArray['config']                        = ['foo' => 'bar'];
        $metadataArray['permissionClasses']['\Foo\Bar'] = '\Foo\Bar';
        $this->assertEquals($metadataArray, $metadata->toArray());
    }
}
