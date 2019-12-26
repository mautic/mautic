<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use MauticPlugin\MarketplaceBundle\Api\Connection;
use MauticPlugin\MarketplaceBundle\Collection\PackageCollection;
use MauticPlugin\MarketplaceBundle\Factory\PackageFactory;

class PluginCollector
{
    private $connection;
    private $packageFactory;

    public function __construct(Connection $connection, PackageFactory $packageFactory)
    {
        $this->connection     = $connection;
        $this->packageFactory = $packageFactory;
    }

    public function collectPackageVersions(string $packageName): PackageCollection
    {
        $payload = $this->connection->getPlugin($packageName);

        return $this->packageFactory->makePackageCollection($payload['packages'][$packageName]);
    }
}
