<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Helper;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\ReloadHelper;

class ReloadHelperTest extends \PHPUnit\Framework\TestCase
{
    private $factoryMock;

    /**
     * @var ReloadHelper
     */
    private $helper;

    /**
     * @var array
     */
    private $sampleAllPlugins = [];

    /**
     * @var array
     */
    private $sampleMetaData = [];

    /**
     * @var array
     */
    private $sampleSchemas = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->factoryMock = $this->createMock(MauticFactory::class);
        $this->helper      = new ReloadHelper($this->factoryMock);

        $this->sampleMetaData = [
            'MauticPlugin\MauticZapierBundle' => [$this->createMock(ClassMetadata::class)],
            'MauticPlugin\MauticCitrixBundle' => [$this->createMock(ClassMetadata::class)],
        ];

        $sampleSchema = $this->createMock(Schema::class);
        $sampleSchema->method('getTables')
                ->willReturn([]);

        $this->sampleSchemas = [
            'MauticPlugin\MauticZapierBundle' => $sampleSchema,
            'MauticPlugin\MauticCitrixBundle' => $sampleSchema,
        ];

        $this->sampleAllPlugins = [
            'MauticZapierBundle' => [
                'isPlugin'          => true,
                'base'              => 'MauticZapier',
                'bundle'            => 'MauticZapierBundle',
                'namespace'         => 'MauticPlugin\MauticZapierBundle',
                'symfonyBundleName' => 'MauticZapierBundle',
                'bundleClass'       => 'Mautic\PluginBundle\Tests\Helper\PluginBundleBaseStub',
                'permissionClasses' => [],
                'relative'          => 'plugins/MauticZapierBundle',
                'directory'         => '/Users/jan/dev/mautic/plugins/MauticZapierBundle',
                'config'            => [
                    'name'        => 'Zapier Integration',
                    'description' => 'Zapier lets you connect Mautic with 1100+ other apps',
                    'version'     => '1.0',
                    'author'      => 'Mautic',
                ],
            ],
            'MauticCitrixBundle' => [
                'isPlugin'          => true,
                'base'              => 'MauticCitrix',
                'bundle'            => 'MauticCitrixBundle',
                'namespace'         => 'MauticPlugin\MauticCitrixBundle',
                'symfonyBundleName' => 'MauticCitrixBundle',
                'bundleClass'       => 'Mautic\PluginBundle\Tests\Helper\PluginBundleBaseStub',
                'permissionClasses' => [],
                'relative'          => 'plugins/MauticCitrixBundle',
                'directory'         => '/Users/jan/dev/mautic/plugins/MauticCitrixBundle',
                'config'            => [
                    'name'        => 'Citrix',
                    'description' => 'Enables integration with Mautic supported Citrix collaboration products.',
                    'version'     => '1.0',
                    'author'      => 'Mautic',
                    'routes'      => [
                        'public' => [
                            'mautic_citrix_proxy' => [
                                'path'       => '/citrix/proxy',
                                'controller' => 'MauticCitrixBundle:Public:proxy',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testDisableMissingPlugins()
    {
        $sampleInstalledPlugins = [
            'MauticZapierBundle'  => $this->createSampleZapierPlugin(),
            'MauticHappierBundle' => $this->createSampleHappierPlugin(),
        ];

        $disabledPlugins = $this->helper->disableMissingPlugins($this->sampleAllPlugins, $sampleInstalledPlugins);

        $this->assertEquals(1, count($disabledPlugins));
        $this->assertEquals('Happier Integration', $disabledPlugins['MauticHappierBundle']->getName());
        $this->assertTrue($disabledPlugins['MauticHappierBundle']->isMissing());
    }

    public function testEnableFoundPlugins()
    {
        $zapierPlugin = $this->createSampleZapierPlugin();
        $zapierPlugin->setIsMissing(true);
        $sampleInstalledPlugins = [
            'MauticZapierBundle' => $zapierPlugin,
            'MauticCitrixBundle' => $this->createSampleCitrixPlugin(),
        ];

        $enabledPlugins = $this->helper->enableFoundPlugins($this->sampleAllPlugins, $sampleInstalledPlugins);

        $this->assertEquals(1, count($enabledPlugins));
        $this->assertEquals('Zapier Integration', $enabledPlugins['MauticZapierBundle']->getName());
        $this->assertFalse($enabledPlugins['MauticZapierBundle']->isMissing());
    }

    public function testUpdatePlugins()
    {
        $this->sampleAllPlugins['MauticZapierBundle']['config']['version']     = '1.0.1';
        $this->sampleAllPlugins['MauticZapierBundle']['config']['description'] = 'Updated description';
        $sampleInstalledPlugins                                                = [
            'MauticZapierBundle'  => $this->createSampleZapierPlugin(),
            'MauticCitrixBundle'  => $this->createSampleCitrixPlugin(),
            'MauticHappierBundle' => $this->createSampleHappierPlugin(),
        ];

        $updatedPlugins = $this->helper->updatePlugins($this->sampleAllPlugins, $sampleInstalledPlugins, $this->sampleMetaData, $this->sampleSchemas);

        $this->assertEquals(1, count($updatedPlugins));
        $this->assertEquals('Zapier Integration', $updatedPlugins['MauticZapierBundle']->getName());
        $this->assertEquals('1.0.1', $updatedPlugins['MauticZapierBundle']->getVersion());
        $this->assertEquals('Updated description', $updatedPlugins['MauticZapierBundle']->getDescription());
    }

    public function testInstallPlugins()
    {
        $sampleInstalledPlugins = [
            'MauticCitrixBundle'  => $this->createSampleCitrixPlugin(),
            'MauticHappierBundle' => $this->createSampleHappierPlugin(),
        ];

        $installedPlugins = $this->helper->installPlugins($this->sampleAllPlugins, $sampleInstalledPlugins, $this->sampleMetaData, $this->sampleSchemas);

        $this->assertEquals(1, count($installedPlugins));
        $this->assertEquals('Zapier Integration', $installedPlugins['MauticZapierBundle']->getName());
        $this->assertEquals('1.0', $installedPlugins['MauticZapierBundle']->getVersion());
        $this->assertEquals('MauticZapierBundle', $installedPlugins['MauticZapierBundle']->getBundle());
        $this->assertEquals('Mautic', $installedPlugins['MauticZapierBundle']->getAuthor());
        $this->assertEquals('Zapier lets you connect Mautic with 1100+ other apps', $installedPlugins['MauticZapierBundle']->getDescription());
        $this->assertFalse($installedPlugins['MauticZapierBundle']->isMissing());
    }

    private function createSampleZapierPlugin()
    {
        $plugin = new Plugin();
        $plugin->setName('Zapier Integration');
        $plugin->setDescription('Zapier lets you connect Mautic with 1100+ other apps');
        $plugin->isMissing(false);
        $plugin->setBundle('MauticZapierBundle');
        $plugin->setVersion('1.0');
        $plugin->setAuthor('Mautic');

        return $plugin;
    }

    private function createSampleCitrixPlugin()
    {
        $plugin = new Plugin();
        $plugin->setName('Citrix');
        $plugin->setDescription('Enables integration with Mautic supported Citrix collaboration products.');
        $plugin->isMissing(false);
        $plugin->setBundle('MauticCitrixBundle');
        $plugin->setVersion('1.0');
        $plugin->setAuthor('Mautic');

        return $plugin;
    }

    private function createSampleHappierPlugin()
    {
        $plugin = new Plugin();
        $plugin->setName('Happier Integration');
        $plugin->setDescription('Happier lets you connect Mautic with 1100+ other apps');
        $plugin->isMissing(false);
        $plugin->setBundle('MauticHappierBundle');
        $plugin->setVersion('1.0');
        $plugin->setAuthor('Mautic');

        return $plugin;
    }
}
