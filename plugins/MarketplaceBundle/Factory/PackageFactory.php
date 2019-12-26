<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Factory;

use MauticPlugin\MarketplaceBundle\Collection\PackageCollection;
use MauticPlugin\MarketplaceBundle\DTO\Package;

class PackageFactory
{
    public function makePackage(array $inputPackage): Package
    {
        $outputPackage = new Package(
            $inputPackage['name'],
            $inputPackage['version_normalized'],
            $inputPackage['version']
        );

        $outputPackage->setDistUrl($inputPackage['dist']['url']);

        if (!empty($inputPackage['extra'])) {
            $outputPackage->setExtra($inputPackage['extra']);
        }

        return $outputPackage;
    }

    public function makePackageCollection(array $packages): PackageCollection
    {
        $collection = new PackageCollection();

        foreach ($packages as $arrayPackage) {
            $collection->add($this->makePackage($arrayPackage));
        }

        return $collection;
    }
}
