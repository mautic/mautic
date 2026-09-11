<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Accessor;

use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;
use PHPUnit\Framework\Attributes\TestDox;

final class ConfigAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array<string, string>
     */
    private array $config = [
        'imap_path' => 'path',
        'user'      => 'user',
        'host'      => 'host',
        'folder'    => 'folder',
    ];

    #[TestDox('All getters return appropriate values')]
    public function testGetters(): void
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertEquals($this->config['imap_path'], $configAccessor->getPath());
        $this->assertEquals($this->config['user'], $configAccessor->getUser());
        $this->assertEquals($this->config['host'], $configAccessor->getHost());
        $this->assertEquals($this->config['folder'], $configAccessor->getFolder());
    }

    #[TestDox('Key is formatted appropriately')]
    public function testKeyIsPathAndUser(): void
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertSame('path_user', $configAccessor->getKey());
    }

    #[TestDox('Test its considered configured if we have a host and a folder')]
    public function testIsConfigured(): void
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertTrue($configAccessor->isConfigured());
    }

    #[TestDox('Test its considered not configured if folder is missing')]
    public function testIsNotConfiguredIfFolderIsMissing(): void
    {
        $config = $this->config;
        unset($config['folder']);
        $configAccessor = new ConfigAccessor($config);
        $this->assertFalse($configAccessor->isConfigured());
    }

    #[TestDox('Test its considered not configured if host is missing')]
    public function testIsNotConfiguredIfHostIsMissing(): void
    {
        $config = $this->config;
        unset($config['host']);
        $configAccessor = new ConfigAccessor($config);
        $this->assertFalse($configAccessor->isConfigured());
    }
}
