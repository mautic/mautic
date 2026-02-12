<?php

namespace Mautic\EmailBundle\MonitoredEmail;

if (!class_exists(MockImap::class)) {
    final class MockImap
    {
        // Enable imap_* function mocks
        public static bool $enabled               = false;
        public static bool $pingReturn            = true;
        public static bool $pingThrowValueError   = false;
        public static bool $reopenReturn          = true;
        public static bool $reopenThrowValueError = false;
        public static int $imapOpenCount          = 0;
        public static int $imapCloseCount         = 0;

        public static function enable(): void
        {
            self::$enabled = true;
            self::reset();
        }

        public static function disable(): void
        {
            self::$enabled = false;
        }

        public static function reset(): void
        {
            self::$pingReturn            = true;
            self::$pingThrowValueError   = false;
            self::$reopenReturn          = true;
            self::$reopenThrowValueError = false;
            self::$imapOpenCount         = 0;
            self::$imapCloseCount        = 0;
        }
    }

    function imap_timeout($type, $timeout): bool
    {
        return MockImap::$enabled ? true : \imap_timeout($type, $timeout);
    }

    function imap_open($path, $user, $password, $options = 0, $retries = 0, $params = null)
    {
        ++MockImap::$imapOpenCount;

        return MockImap::$enabled ? new \stdClass() : \imap_open($path, $user, $password, $options, $retries, $params);
    }

    function imap_ping($stream): bool
    {
        if (!MockImap::$enabled) {
            return \imap_ping($stream);
        }
        if (MockImap::$pingThrowValueError) {
            throw new \ValueError('IMAP connection is already closed');
        }

        return MockImap::$pingReturn;
    }

    function imap_reopen($stream, $path): bool
    {
        if (!MockImap::$enabled) {
            return \imap_reopen($stream, $path);
        }
        if (MockImap::$reopenThrowValueError) {
            throw new \ValueError('IMAP connection is already closed');
        }

        return MockImap::$reopenReturn;
    }

    function imap_close($stream, $flags = 0): bool
    {
        ++MockImap::$imapCloseCount;

        return MockImap::$enabled ? true : \imap_close($stream, $flags);
    }

    function imap_errors(): array
    {
        return MockImap::$enabled ? [] : \imap_errors();
    }

    function imap_alerts(): array
    {
        return MockImap::$enabled ? [] : \imap_alerts();
    }
}

namespace Mautic\EmailBundle\Tests\MonitoredEmail;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\MonitoredEmail\MockImap;

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
            'EmailBundle_unsubscribes' => [
                'address'           => 'barfoo@foo.com',
                'host'              => 'imap.barfoo.com',
                'port'              => '993',
                'encryption'        => '/ssl/novalidate-cert',
                'user'              => 'barfoo@foo.com',
                'password'          => 'ultrasecret',
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

        $settings = $mailbox->getMailboxSettings('EmailBundle', 'unsubscribes');

        $this->assertArrayHasKey('folder', $settings);
        $this->assertEquals('INBOX', $settings['folder']);
        $this->assertEquals('barfoo@foo.com', $settings['address']);
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

    public function testIsConnectedReturnsFalseOnValueError(): void
    {
        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->method('get')->willReturn(
            [
                'general' => [
                    'host'     => 'localhost',
                    'port'     => '993',
                    'user'     => 'test',
                    'password' => 'test',
                ],
            ]
        );

        $pathsHelper = $this->createMock(PathsHelper::class);

        $mailbox = new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        $reflection = new \ReflectionClass($mailbox);

        // Set a value that is not an IMAP\Connection resource to trigger ValueError in imap_ping
        $imapStreamProperty = $reflection->getProperty('imapStream');
        $imapStreamProperty->setValue($mailbox, new \stdClass());

        $isConnectedMethod = $reflection->getMethod('isConnected');

        $this->assertFalse($isConnectedMethod->invoke($mailbox));
    }

    public function testGetImapStreamHandlesErrorOnReopen(): void
    {
        $parametersHelper = $this->createMock(CoreParametersHelper::class);
        $parametersHelper->method('get')->willReturn([
            'general' => [
                'host'     => 'localhost',
                'port'     => '993',
                'user'     => 'test',
                'password' => 'test',
            ],
        ]);

        $pathsHelper = $this->createMock(PathsHelper::class);

        MockImap::enable();
        $mailbox = new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);

        // opens connection
        $mailbox->getImapStream();
        $this->assertEquals(1, MockImap::$imapOpenCount);

        // Case 1: imap_reopen returns false
        MockImap::$reopenReturn = false;
        $mailbox->getImapStream();

        // Should close the old stream and open a new one
        $this->assertEquals(2, MockImap::$imapOpenCount);
        $this->assertEquals(1, MockImap::$imapCloseCount);

        // Case 2: imap_reopen throws ValueError
        MockImap::reset();
        MockImap::$reopenReturn          = true;
        MockImap::$reopenThrowValueError = true;

        $mailbox->getImapStream();
        // Should again close the old stream and open a new one
        $this->assertEquals(1, MockImap::$imapOpenCount);
        $this->assertEquals(1, MockImap::$imapCloseCount);
        MockImap::disable();
    }
}
