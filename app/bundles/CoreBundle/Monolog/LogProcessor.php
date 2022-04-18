<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Monolog;

class LogProcessor
{
    public function __invoke(array $record): array
    {
        $record['extra']['gethostname'] = gethostname();
        $record['extra']['getmypid']    = getmypid();

        return $record;
    }
}
