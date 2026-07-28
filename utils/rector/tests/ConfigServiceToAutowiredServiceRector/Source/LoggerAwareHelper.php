<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source;

use Monolog\Logger;

final class LoggerAwareHelper
{
    public function __construct(
        private Logger $logger,
    ) {
    }
}
