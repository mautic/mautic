<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;

class AssetControllerFunctionalTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $asset = new Asset();
        $asset->setTitle('test');
        $asset->setAlias('test');
        $asset->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $asset->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $asset->setCreatedByUser('Test User');

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        $urlAlias   = 'assets';
        $routeAlias = 'asset';
        $column     = 'dateModified';
        $column2    = 'title';
        $tableAlias = 'a.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }
}
