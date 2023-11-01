<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Exception;

class ObjectNotSupportedException extends \Exception
{
    public function __construct(string $integration, string $object)
    {
        parent::__construct("$integration does not support a $object object");
    }
}
