<?php

namespace Mautic\PluginBundle\Tests\Helper;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\Helper\ReloadHelper;
use Mautic\PluginBundle\PluginEvents;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReloadHelperTest extends \PHPUnit\Framework\TestCase
{
    private ReloadHelper $helper;

    private array $sampleAllPlugins = [];

    private array $sampleMetaData = [];

    private array $sampleSchemas = [];

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->helper          = new ReloadHelper($this->eventDispatcher);

        $this->sampleMetaData = [
            'MauticPlugin\MauticZapierBundle' => [
                'MauticPlugin\MauticZapierBundle\Entity\SomeTest' => $this->createMock(ClassMetadata::class),
            ],
        ];

        $sampleSchema = $this->createMock(Schema::class);
        $sampleSchema->method('getTables')
                ->willReturn([]);

        $this->sampleSchemas = [
            'MauticPlugin\MauticZapierBundle' => $sampleSchema,
        ];

        $this->sampleAllPlugins = [
            'MauticZapierBundle' => [
                'isPlugin'          => true,
                'base'              => 'MauticZapier',
                'bundle'            => 'MauticZapierBundle',
                'namespace'         => 'MauticPlugin\MauticZapierBundle',
                'symfonyBundleName' => 'MauticZapierBundle',
                'bundleClass'       => PluginBundleBaseStub::class,
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
        ];
    }

    public function testDisableMissingPlugins(): void
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

    public function testEnableFoundPlugins(): void
    {
        $zapierPlugin = $this->createSampleZapierPlugin();
        $zapierPlugin->setIsMissing(true);
        $sampleInstalledPlugins = [
            'MauticZapierBundle' => $zapierPlugin,
        ];

        $enabledPlugins = $this->helper->enableFoundPlugins($this->sampleAllPlugins, $sampleInstalledPlugins);

        $this->assertEquals(1, count($enabledPlugins));
        $this->assertEquals('Zapier Integration', $enabledPlugins['MauticZapierBundle']->getName());
        $this->assertFalse($enabledPlugins['MauticZapierBundle']->isMissing());
    }

    public function testUpdatePlugins(): void
    {
        $this->sampleAllPlugins['MauticZapierBundle']['config']['version']     = '1.0.1';
        $this->sampleAllPlugins['MauticZapierBundle']['config']['description'] = 'Updated description';
        $sampleInstalledPlugins                                                = [
            'MauticZapierBundle'  => $this->createSampleZapierPlugin(),
            'MauticHappierBundle' => $this->createSampleHappierPlugin(),
        ];
        $plugin = $this->createSampleZapierPlugin();
        $plugin->setVersion('1.0.1');
        $plugin->setDescription('Updated description');
        $event = new PluginUpdateEvent(
            $plugin,
            '1.0',
            $this->sampleMetaData['MauticPlugin\MauticZapierBundle'],
            $this->sampleSchemas['MauticPlugin\MauticZapierBundle']
        );
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($event, PluginEvents::ON_PLUGIN_UPDATE);
        $updatedPlugins = $this->helper->updatePlugins($this->sampleAllPlugins, $sampleInstalledPlugins, $this->sampleMetaData, $this->sampleSchemas);

        $this->assertEquals(1, count($updatedPlugins));
        $this->assertEquals('Zapier Integration', $updatedPlugins['MauticZapierBundle']->getName());
        $this->assertEquals('1.0.1', $updatedPlugins['MauticZapierBundle']->getVersion());
        $this->assertEquals('Updated description', $updatedPlugins['MauticZapierBundle']->getDescription());
    }

    public function testInstallPlugins(): void
    {
        $sampleInstalledPlugins = [
            'MauticHappierBundle' => $this->createSampleHappierPlugin(),
        ];
        $event = new PluginInstallEvent(
            $this->createSampleZapierPlugin(),
            $this->sampleMetaData['MauticPlugin\MauticZapierBundle'],
            null
        );
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($event, PluginEvents::ON_PLUGIN_INSTALL);

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
