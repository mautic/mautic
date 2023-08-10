<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object;

use Mautic\LeadBundle\Entity\Lead;

final class Contact implements ObjectInterface
{
    public const NAME   = 'lead'; // kept as lead for BC
    public const ENTITY = Lead::class;

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
