<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\DependencyInjection\Builder\Metadata\ConfigMetadata;
use Mautic\CoreBundle\IpLookup\ExtremeIpLookup;
use PHPUnit\Framework\TestCase;

final class ConfigMetadataTest extends TestCase
{
    //    /**
    //     * @var BundleMetadata|MockObject
    //     */
    //    private MockObject $metadata;

    //    protected function setUp(): void
    //    {
    // //        $this->metadata = new BundleMetadata();
    // //            ->onlyMethods(['getDirectory'])
    // //            ->disableOriginalConstructor()
    // //            ->getMock();
    //    }

    public function testMissingConfigIsIgnored(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => '/foo/bar',
        ]);

        //        $this->metadata->expects($this->once())
        //            ->method('getDirectory')
        //            ->willReturn('/foo/bar');

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $this->assertEquals([], $bundleMetadata->toArray()['config']);
    }

    public function testBadConfigIsIgnored(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/BadConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $this->assertEquals([], $bundleMetadata->toArray()['config']);
    }

    public function testIpLookupServicesAreLoaded(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $this->assertSame(
            [
                'extreme-ip' => [
                    'display_name' => 'Extreme-IP',
                    'class'        => ExtremeIpLookup::class,
                ],
            ],
            $configMetadata->getIpLookupServices()
        );
    }

    public function testConfigIsLoaded(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $config = $bundleMetadata->toArray()['config'];
        $this->assertArrayHasKey('mautic.helper.bundle', $config['services']['helpers']);
        $this->assertArrayHasKey('log_path', $config['parameters']);
    }

    public function testOptionalMissingServicesAreIgnored(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $config = $bundleMetadata->toArray()['config'];
        $this->assertArrayNotHasKey('mautic.test.fixture', $config['services']['fixtures']);
    }

    public function testParameterArgumentsAreEncoded(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $config = $bundleMetadata->toArray()['config'];
        $this->assertArrayHasKey('mautic.helper.bundle', $config['services']['helpers']);

        $this->assertEquals('%%mautic.bundles%%', $config['services']['helpers']['mautic.helper.bundle']['arguments'][0]);
    }

    public function testParametersAreEncoded(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $config = $bundleMetadata->toArray()['config'];
        $this->assertArrayHasKey('log_path', $config['parameters']);

        $this->assertEquals('%%kernel.project_dir%%/var/logs', $config['parameters']['log_path']);
    }

    public function testParameterTypesArePreserved(): void
    {
        $bundleMetadata = new BundleMetadata([
            'directory' => __DIR__.'/resource/GoodConfig',
        ]);

        $configMetadata = new ConfigMetadata($bundleMetadata);
        $configMetadata->build();

        $config = $bundleMetadata->toArray()['config'];
        $this->assertArrayHasKey('log_path', $config['parameters']);

        $this->assertEquals('%%kernel.project_dir%%/var/logs', $config['parameters']['log_path']);
        $this->assertEquals(7, $config['parameters']['max_log_files']);
        $this->assertEquals('media/images', $config['parameters']['image_path']);
        $this->assertEquals(false, $config['parameters']['bool_value']);
        $this->assertEquals(null, $config['parameters']['null_value']);
        $this->assertEquals([], $config['parameters']['array_value']);
    }
}
