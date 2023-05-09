<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Monolog;

class LogProcessor
{
    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        $record['extra'] = [
            'hostname' => gethostname(),
            'pid'      => getmypid(),
        ];

        return $record;
    }
}
