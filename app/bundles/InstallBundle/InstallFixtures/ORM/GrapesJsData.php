<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\UserBundle\Entity\Role;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GrapesJsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureGroupInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $parameters = include __DIR__.'/../../../../../plugins/GrapesJsBuilderBundle/Config/config.php';

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
        $manager->flush();

        $integration = new Integration();
        $integration->setIsPublished(true);
        $integration->setName('GrapesJsBuilder');
        $integration->setPlugin($plugin);

        $manager->persist($integration);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
