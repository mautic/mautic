<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Model;

use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Service\Allowlist;

class PackageModel
{
    private Connection $connection;
    private Allowlist $allowlist;

    public function __construct(Connection $connection, Allowlist $allowlist)
    {
        $this->connection = $connection;
        $this->allowlist  = $allowlist;
    }

    public function getPackageDetail(string $name): PackageDetail
    {
        $allowlist      = $this->allowlist->getAllowList();
        $allowedPackage = $allowlist->findPackageByName($name);
        $payload        = $this->connection->getPackage($name);

        return PackageDetail::fromArray($payload['package'] + $allowedPackage->toArray());
    }
}
