<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\DTO;

final class ConsoleOutput
{
    public function __construct(
        /**
         * Console exit code. 0 when everything went fine, or an error code.
         */
        public int $exitCode,
        public string $output,
    ) {
    }
}
