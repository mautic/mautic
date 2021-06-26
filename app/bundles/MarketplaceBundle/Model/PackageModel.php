<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Model;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\DTO\PackageDetail;

class PackageModel
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getPackageDetail(string $name): PackageDetail
    {
        $payload = $this->connection->getPackage($name);

        return PackageDetail::fromArray($payload['package']);
    }
}
