<?php

namespace Mautic\CoreBundle\Tests\Unit\DependencyInjection\Builder\Metadata;

use Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadata;
use Mautic\CoreBundle\DependencyInjection\Builder\Metadata\ConfigMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigMetadataTest extends TestCase
{
    /**
     * @var BundleMetadata|MockObject
     */
    private $metadata;

    protected function setUp(): void
    {
        $this->metadata = $this->getMockBuilder(BundleMetadata::class)
            ->setMethodsExcept(['setConfig', 'toArray'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testMissingConfigIsIgnored()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn('/foo/bar');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $this->assertEquals([], $this->metadata->toArray()['config']);
    }

    public function testBadConfigIsIgnored()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/BadConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $this->assertEquals([], $this->metadata->toArray()['config']);
    }

    public function testIpLookupServicesAreLoaded()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
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
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $config = $this->metadata->toArray()['config'];
        $this->assertTrue(isset($config['services']['helpers']['mautic.helper.bundle']));
        $this->assertTrue(isset($config['parameters']['log_path']));
    }

    public function testOptionalMissingServicesAreIgnored()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $config = $this->metadata->toArray()['config'];
        $this->assertFalse(isset($config['services']['fixtures']['mautic.test.fixture']));
    }

    public function testParameterArgumentsAreEncoded()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $config = $this->metadata->toArray()['config'];
        $this->assertTrue(isset($config['services']['helpers']['mautic.helper.bundle']));

        $this->assertEquals('%%mautic.bundles%%', $config['services']['helpers']['mautic.helper.bundle']['arguments'][0]);
    }

    public function testParametersAreEncoded()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $config = $this->metadata->toArray()['config'];
        $this->assertTrue(isset($config['parameters']['log_path']));

        $this->assertEquals('%%kernel.root_dir%%/../var/logs', $config['parameters']['log_path']);
    }

    public function testParameterTypesArePreserved()
    {
        $this->metadata->expects($this->once())
            ->method('getDirectory')
            ->willReturn(__DIR__.'/resource/GoodConfig');

        $configMetadata = new ConfigMetadata($this->metadata);
        $configMetadata->build();

        $config = $this->metadata->toArray()['config'];
        $this->assertTrue(isset($config['parameters']['log_path']));

        $this->assertEquals('%%kernel.root_dir%%/../var/logs', $config['parameters']['log_path']);
        $this->assertEquals(7, $config['parameters']['max_log_files']);
        $this->assertEquals('media/images', $config['parameters']['image_path']);
        $this->assertEquals(false, $config['parameters']['bool_value']);
        $this->assertEquals(null, $config['parameters']['null_value']);
        $this->assertEquals([], $config['parameters']['array_value']);
    }
}
