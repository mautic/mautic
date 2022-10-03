<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Model;

class ConsoleOutputModel
{
    /**
     * Console exit code. 0 when everything went fine, or an error code.
     */
    public int $exitCode;
    public string $output;

    public function __construct(int $exitCode, string $output)
    {
        $this->exitCode = $exitCode;
        $this->output   = $output;
    }
}
