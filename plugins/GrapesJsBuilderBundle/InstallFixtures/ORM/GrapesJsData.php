<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;

class GrapesJsData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    public function load(ObjectManager $manager): void
    {
        $applicationDir           = $this->coreParametersHelper->get('mautic.application_dir');
        $grapeJsBuilderConfigPath = $applicationDir.'/plugins/GrapesJsBuilderBundle/Config/config.php';

        if (!file_exists($grapeJsBuilderConfigPath)) {
            return;
        }

        $parameters = include $grapeJsBuilderConfigPath;

        if (!is_array($parameters)) {
            return;
        }

        $plugin = new Plugin();
        $plugin->setName($parameters['name']);
        $plugin->setDescription($parameters['description']);
        $plugin->setVersion($parameters['version']);
        $plugin->setAuthor($parameters['author']);
        $plugin->setBundle('GrapesJsBuilderBundle');
        $manager->persist($plugin);

        $integration = new Integration();
        $integration->setIsPublished(true);
        $integration->setName('GrapesJsBuilder');
        $integration->setPlugin($plugin);
        $manager->persist($integration);

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 1;
    }
}
