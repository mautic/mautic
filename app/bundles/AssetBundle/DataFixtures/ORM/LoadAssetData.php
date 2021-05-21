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
use Doctrine\Persistence\ObjectManager;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;

class LoadAssetData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var AssetModel
     */
    private $assetModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(AssetModel $assetModel)
    {
        $this->assetModel = $assetModel;
    }

    public function load(ObjectManager $manager)
    {
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
            $this->assetModel->getRepository()->saveEntity($asset);
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
