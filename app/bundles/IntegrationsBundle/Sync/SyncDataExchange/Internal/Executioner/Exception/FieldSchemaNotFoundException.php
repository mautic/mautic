<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\Exception;

class FieldSchemaNotFoundException extends \Exception
{
    public function __construct(string $object, string $alias)
    {
        parent::__construct(sprintf('Schema for alias "%s" of object "%s" not found', $alias, $object));
    }
}
