<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\DependencyInjection\Builder\Metadata\ConfigMetadata;
use PHPUnit\Framework\TestCase;

class ConfigMetadataTest extends TestCase
{
    public function testMissingConfigIsIgnored()
    {
        $metadata       = new BundleMetadata(['directory' => 'foo/bar', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $this->assertEquals([], $metadata->toArray()['config']);
    }

    public function testBadConfigIsIgnored()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/BadConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $this->assertEquals([], $metadata->toArray()['config']);
    }

    public function testIpLookupServicesAreLoaded()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $this->assertEquals(
            [
                'extreme-ip' => [
                    'display_name' => 'Extreme-IP',
                    'class'        => 'Mautic\CoreBundle\IpLookup\ExtremeIpLookup',
                ],
            ],
            $configMetadata->getIpLookupServices()
        );
    }

    public function testConfigIsLoaded()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $config = $metadata->toArray()['config'];
        $this->assertTrue(isset($config['services']['helpers']['mautic.helper.bundle']));
        $this->assertTrue(isset($config['parameters']['log_path']));
    }

    public function testOptionalMissingServicesAreIgnored()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $config = $metadata->toArray()['config'];
        $this->assertFalse(isset($config['services']['fixtures']['mautic.test.fixture']));
    }

    public function testParameterArgumentsAreEncoded()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $config = $metadata->toArray()['config'];
        $this->assertTrue(isset($config['services']['helpers']['mautic.helper.bundle']));

        $this->assertEquals('%%mautic.bundles%%', $config['services']['helpers']['mautic.helper.bundle']['arguments'][0]);
    }

    public function testParametersAreEncoded()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $config = $metadata->toArray()['config'];
        $this->assertTrue(isset($config['parameters']['log_path']));

        $this->assertEquals('%%kernel.project_dir%%/var/logs', $config['parameters']['log_path']);
    }

    public function testParameterTypesArePreserved()
    {
        $metadata       = new BundleMetadata(['directory' => __DIR__.'/resource/GoodConfig', 'namespace' => 'test', 'bundle' => 'test', 'symfonyBundleName' => 'test']);
        $configMetadata = new ConfigMetadata($metadata);
        $configMetadata->build();

        $config = $metadata->toArray()['config'];
        $this->assertTrue(isset($config['parameters']['log_path']));

        $this->assertEquals('%%kernel.project_dir%%/var/logs', $config['parameters']['log_path']);
        $this->assertEquals(7, $config['parameters']['max_log_files']);
        $this->assertEquals('media/images', $config['parameters']['image_path']);
        $this->assertEquals(false, $config['parameters']['bool_value']);
        $this->assertEquals(null, $config['parameters']['null_value']);
        $this->assertEquals([], $config['parameters']['array_value']);
    }
}
