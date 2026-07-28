<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Exception;

final class ObjectNotFoundException extends \Exception
{
    public function __construct(string $object)
    {
        parent::__construct("{$object} was not found in the mapping");
    }
}
