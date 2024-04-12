<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Exception;

class DatabaseVersionTooOldException extends \Exception
{
    public function __construct(
        private string $currentVersion
    ) {
        parent::__construct();
    }

    /**
     * Returns the current database version as reported by the database itself.
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }
}
