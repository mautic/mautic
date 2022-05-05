<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object;

use Mautic\LeadBundle\Entity\Company as CompanyEntity;

final class Company implements ObjectInterface
{
    const NAME   = 'company';
    const ENTITY = CompanyEntity::class;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName(): string
    {
        return self::ENTITY;
    }
}
