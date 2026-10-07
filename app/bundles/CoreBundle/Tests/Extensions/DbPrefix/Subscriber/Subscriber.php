<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Extensions\DbPrefix\Subscriber;

use Mautic\CoreBundle\Tests\Extensions\DbPrefix\DbPrefix;

abstract class Subscriber
{
    public function __construct(
        private readonly DbPrefix $dbPrefix,
    ) {
    }

    public function dbPrefix(): DbPrefix
    {
        return $this->dbPrefix;
    }
}
