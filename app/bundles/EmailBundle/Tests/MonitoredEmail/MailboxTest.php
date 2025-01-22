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

        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parametersHelper->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->will($this->returnValue(__DIR__.'/../../../../cache/'));

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

        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parametersHelper->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->will($this->returnValue(__DIR__.'/../../../../cache/'));

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

        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parametersHelper->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $parametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parametersHelper->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('tmp', true)
            ->will($this->returnValue(__DIR__.'/../../../../cache/tmp'));

        new \Mautic\EmailBundle\MonitoredEmail\Mailbox($parametersHelper, $pathsHelper);
    }

    public function testConvertStringEncoding(): void
    {
        $mailbox = $this->getMockBuilder(\Mautic\EmailBundle\MonitoredEmail\Mailbox::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $reflection = new \ReflectionClass(\Mautic\EmailBundle\MonitoredEmail\Mailbox::class);
        $method     = $reflection->getMethod('convertStringEncoding');
        $method->setAccessible(true);

        $this->assertTrue(extension_loaded('mbstring'), 'mbstring extension is loaded');

        // Prepare 1. test data: valid fromEncoding
        $string       = 'some UTF8 text with öüóőúéáűíä and ÖÜÓŐÚÉÁŰÍßÄŁ';
        $fromEncoding = 'UTF-8';
        $toEncoding   = 'ISO-8859-1';
        $result       = $method->invoke($mailbox, $string, $fromEncoding, $toEncoding);

        // Assert the expected outcome
        $this->assertNotNull($result);
        $this->assertNotSame($string, $result, 'Converted string should differ from the original string.');

        // Prepare 2. test data: fromEncoding not differ from toEncoding
        $fromEncoding2 = $toEncoding2 = 'UTF-8';
        $result2       = $method->invoke($mailbox, $string, $fromEncoding2, $toEncoding2);
        $this->assertNotNull($result);
        $this->assertSame($string, $result2, 'The string should not differ from the original string.');

        // Prepare 3. test data: with invalid fromEncoding
        $string3       = 'some text with special chars: öüóőúéáűíä and ÖÜÓŐÚÉÁŰÍßÄŁ';
        $fromEncoding3 = 'unicode-1-1-utf'; // invalid fromEncoding
        $toEncoding3   = 'UTF-8';
        $result3       = $method->invoke($mailbox, $string3, $fromEncoding3, $toEncoding3);
        $this->assertNotNull($result);
        $this->assertSame($string3, $result3, 'The string should not differ from the original string.');
    }
}
