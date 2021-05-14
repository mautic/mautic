<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
 * Creates pagehit url prefix index. Cannot be done in the entity itself because doctrine
 * doesn't support prefix indexes :(
 */
class PageHitIndex extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureGroupInterface
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
        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        try {
            $manager->getConnection()->exec("CREATE INDEX {$prefix}page_hit_url ON {$prefix}page_hits (url(128))");
        } catch (DriverException $exception) {
            if (1061 !== $exception->getErrorCode()) {
                // If not 'Index already exists' error, throw the error
                throw $exception;
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 99;
    }
}
