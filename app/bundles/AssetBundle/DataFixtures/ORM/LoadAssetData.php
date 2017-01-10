<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\AssetBundle\Entity\Asset;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadAssetData.
 */
class LoadAssetData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repo = $this->container->get('mautic.asset.model.asset')->getRepository();

        $asset = new Asset();
        $asset
            ->setTitle('@TOCHANGE: Asset1 Title')
            ->setAlias('asset1')
            ->setOriginalFileName('@TOCHANGE: Asset1 Original File Name')
            ->setPath('fdb8e28357b02d12d068de3e5661832e21bc08ec.doc')
            ->setDownloadCount(1)
            ->setUniqueDownloadCount(1)
            ->setRevision(1)
            ->setLanguage('en');

        try {
            $repo->saveEntity($asset);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 10;
    }
}
