<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Accessor;

use Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor;

class ConfigAccessorTest extends \PHPUnit_Framework_TestCase
{
    protected $config = [
        'imap_path' => 'path',
        'user'      => 'user',
        'host'      => 'host',
        'folder'    => 'folder',
    ];

    /**
     * @testdox All getters return appropriate values
     *
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getPath()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getUser()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getHost()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getFolder()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getProperty()
     */
    public function testGetters()
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertEquals($this->config['imap_path'], $configAccessor->getPath());
        $this->assertEquals($this->config['user'], $configAccessor->getUser());
        $this->assertEquals($this->config['host'], $configAccessor->getHost());
        $this->assertEquals($this->config['folder'], $configAccessor->getFolder());
    }

    /**
     * @testdox Key is formatted appropriately
     *
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getKey()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getHost()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getFolder()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getProperty()
     */
    public function testKeyIsPathAndUser()
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertEquals('path_user', $configAccessor->getKey());
    }

    /**
     * @testdox Test its considered configured if we have a host and a folder
     *
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::isConfigured()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getHost()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getFolder()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getProperty()
     */
    public function testIsConfigured()
    {
        $configAccessor = new ConfigAccessor($this->config);

        $this->assertTrue($configAccessor->isConfigured());
    }

    /**
     * @testdox Test its considered not configured if folder is missing
     *
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::isConfigured()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getHost()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getFolder()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getProperty()
     */
    public function testIsNotConfiguredIfFolderIsMissing()
    {
        $config = $this->config;
        unset($config['folder']);
        $configAccessor = new ConfigAccessor($config);
        $this->assertFalse($configAccessor->isConfigured());
    }

    /**
     * @testdox Test its considered not configured if host is missing
     *
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::isConfigured()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getHost()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getFolder()
     * @covers \Mautic\EmailBundle\MonitoredEmail\Accessor\ConfigAccessor::getProperty()
     */
    public function testIsNotConfiguredIfHostIsMissing()
    {
        $config = $this->config;
        unset($config['host']);
        $configAccessor = new ConfigAccessor($config);
        $this->assertFalse($configAccessor->isConfigured());
    }
}
