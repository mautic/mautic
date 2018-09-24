<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
 * creates pagehit url prefix index. Cannot be done in the entity itself because doctrine
 * doesn't support prefix indexes :(
 */
class PageHitIndex extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        $manager->getConnection()->exec("CREATE INDEX {$prefix}page_hit_url ON {$prefix}page_hits (url(128))");
        $manager->flush();
    }

    public function getOrder()
    {
        return 99;
    }
}
