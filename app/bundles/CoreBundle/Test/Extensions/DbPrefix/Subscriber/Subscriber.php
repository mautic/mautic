<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\DbPrefix\Subscriber;

use Mautic\CoreBundle\Test\Extensions\DbPrefix\DbPrefix;

abstract class Subscriber
{
    private DbPrefix $dbPrefix;

    public function __construct(DbPrefix $separateProcess)
    {
        $this->dbPrefix = $separateProcess;
    }

    public function dbPrefix(): DbPrefix
    {
        return $this->dbPrefix;
    }
}
