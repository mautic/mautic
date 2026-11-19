<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

trait DependencyErrorEventTrait
{
    /**
     * @var string[] Array of dependency error messages
     */
    private array $dependencyErrors = [];

    public function addDependencyError(string $error): void
    {
        $this->dependencyErrors[] = $error;
    }

    public function getDependencyErrors(): array
    {
        return $this->dependencyErrors;
    }
}
