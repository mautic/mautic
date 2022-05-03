<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object;

interface ObjectInterface
{
    /**
     * Returns name key of the object.
     */
    public function getName(): string;

    /**
     * Returns full Doctrine entity class name of the object.
     */
    public function getEntityName(): string;
}
