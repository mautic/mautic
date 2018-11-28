<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;

/**
 * Class Mailbox.
 */
class MailboxTest extends \PHPUnit_Framework_TestCase
{
    public function testSettingsForMonitoredEmailWithoutOverride()
    {
        $config = [
            'general' => [
                'address'    => 'foo@bar.com',
                'host'       => 'imap.bar.com',
                'port'       => '993',
                'encryption' => '/ssl',
                'user'       => 'foo@bar.com',
                'password'   => 'topsecret',
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
            ->method('getParameter')
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

    public function testSettingsForMonitoredEmailWithOverride()
    {
        $config = [
            'general' => [
                'address'    => 'foo@bar.com',
                'host'       => 'imap.bar.com',
                'port'       => '993',
                'encryption' => '/ssl',
                'user'       => 'foo@bar.com',
                'password'   => 'topsecret',
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
            ->method('getParameter')
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
}
