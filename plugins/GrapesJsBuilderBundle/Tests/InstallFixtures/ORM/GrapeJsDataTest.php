<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\InstallFixtures\ORM;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\GrapesJsBuilderBundle\InstallFixtures\ORM\GrapesJsData;
use PHPUnit\Framework\Assert;

class GrapeJsDataTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testGetGroups(): void
    {
        Assert::assertSame(['group_install', 'group_mautic_install_data'], GrapesJsData::getGroups());
    }

    public function testLoad(): void
    {
        $findOneByCriteria = [
            'name'        => 'GrapesJS Builder',
            'description' => 'GrapesJS Builder with MJML support for Mautic',
            'version'     => '1.0.0',
            'author'      => 'Mautic Community',
            'bundle'      => 'GrapesJsBuilderBundle',
        ];
        $plugin = $this->em->getRepository(Plugin::class)->findOneBy($findOneByCriteria);
        self::assertNull($plugin);

        $this->loadFixtures([GrapesJsData::class]);

        $plugin = $this->em->getRepository(Plugin::class)->findOneBy($findOneByCriteria);
        self::assertInstanceOf(Plugin::class, $plugin);

        $integration = $this->em->getRepository(Integration::class)->findOneBy(
            [
                'isPublished' => true,
                'name'        => 'GrapesJsBuilder',
                'plugin'      => $plugin,
            ]
        );
        self::assertInstanceOf(Integration::class, $integration);
    }
}
