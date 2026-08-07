<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object;

use Mautic\LeadBundle\Entity\Company as CompanyEntity;

final class Company implements ObjectInterface
{
    public const string NAME   = 'company';

    public const string ENTITY = CompanyEntity::class;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getEntityName(): string
    {
        return self::ENTITY;
    }
}
