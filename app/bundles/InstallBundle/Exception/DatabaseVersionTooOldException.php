<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Exception;

class DatabaseVersionTooOldException extends \Exception
{
    private string $currentVersion;

    public function __construct(string $currentVersion)
    {
        parent::__construct();
        $this->currentVersion = $currentVersion;
    }

    /**
     * Returns the current database version as reported by the database itself.
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }
}
