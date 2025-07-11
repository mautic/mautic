<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;

class MailboxTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithDefaultConfig(): void
    {
        $expected = [
            'host'            => '',
            'port'            => '',
            'password'        => '',
            'user'            => '',
            'encryption'      => '',
            'use_attachments' => false,
        ];

        $parametersHelper = $this->createMock(CoreParametersHelper::class);

        $pathsHelper = $this->createMock(PathsHelper::class);

        $mailbox = new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        $this->assertEquals($expected, $mailbox->getMailboxSettings());
    }

    public function testSettingsForMonitoredEmailWithoutOverride(): void
    {
        $config = [
            'general' => [
                'address'         => 'foo@bar.com',
                'host'            => 'imap.bar.com',
                'port'            => '993',
                'encryption'      => '/ssl',
                'user'            => 'foo@bar.com',
                'password'        => 'topsecret',
                'use_attachments' => true,
            ],
            'EmailBundle_bounces' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'Bounces',
            ],
        ];

        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->expects($this->once())
            ->method('get')
            ->willReturn($config);

        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->willReturn(__DIR__.'/../../../../cache/');

        $mailbox = new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        $settings = $mailbox->getMailboxSettings('EmailBundle', 'bounces');

        $this->assertArrayHasKey('folder', $settings);
        $this->assertEquals('Bounces', $settings['folder']);
        $this->assertEquals('foo@bar.com', $settings['address']);
    }

    public function testSettingsForMonitoredEmailWithOverride(): void
    {
        $config = [
            'general' => [
                'address'         => 'foo@bar.com',
                'host'            => 'imap.bar.com',
                'port'            => '993',
                'encryption'      => '/ssl',
                'user'            => 'foo@bar.com',
                'password'        => 'topsecret',
                'use_attachments' => true,
            ],
            'EmailBundle_bounces' => [
                'address'           => 'bar@foo.com',
                'host'              => 'imap.foo.com',
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => 'bar@foo.com',
                'password'          => 'topsecret',
                'override_settings' => true,
                'folder'            => 'INBOX',
            ],
        ];

        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->expects($this->once())
            ->method('get')
            ->willReturn($config);

        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->willReturn(__DIR__.'/../../../../cache/');

        $mailbox = new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        $settings = $mailbox->getMailboxSettings('EmailBundle', 'bounces');

        $this->assertArrayHasKey('folder', $settings);
        $this->assertEquals('INBOX', $settings['folder']);
        $this->assertEquals('bar@foo.com', $settings['address']);
    }

    public function testUseAttachments(): void
    {
        // Test undefined $this->settings['use_attachments']
        // will not invoke undefined index error or mkdir error
        $config = [
            'general' => [
                'address'         => 'foo@bar.com',
                'host'            => 'imap.bar.com',
                'port'            => '993',
                'encryption'      => '/ssl',
                'user'            => 'foo@bar.com',
                'password'        => 'topsecret',
            ],
        ];

        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->expects($this->once())
            ->method('get')
            ->willReturn($config);

        $pathsHelper = $this->createMock(PathsHelper::class);

        new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        // Test $this->settings['use_attachments'] == true
        // dir creation is not failing
        $config = [
            'general' => [
                'address'         => 'foo@bar.com',
                'host'            => 'imap.bar.com',
                'port'            => '993',
                'encryption'      => '/ssl',
                'user'            => 'foo@bar.com',
                'password'        => 'topsecret',
                'use_attachments' => true,
            ],
        ];

        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->expects($this->once())
            ->method('get')
            ->willReturn($config);

        $pathsHelper = $this->createMock(PathsHelper::class);
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('tmp', true)
            ->willReturn(__DIR__.'/../../../../cache/tmp');

        new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);
    }
}
