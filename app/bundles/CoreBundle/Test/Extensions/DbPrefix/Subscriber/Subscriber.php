<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\DbPrefix\Subscriber;

use Mautic\CoreBundle\Test\Extensions\DbPrefix\DbPrefix;

abstract class Subscriber
{
    public function __construct(private DbPrefix $dbPrefix)
    {
    }

    public function dbPrefix(): DbPrefix
    {
        return $this->dbPrefix;
    }
}
