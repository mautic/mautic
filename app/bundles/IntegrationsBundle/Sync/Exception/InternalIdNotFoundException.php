<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Exception;

class InternalIdNotFoundException extends \Exception
{
    public function __construct(string $object)
    {
        parent::__construct("ID for object $object not found");
    }
}
