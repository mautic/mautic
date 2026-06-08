<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

interface DependencyErrorEventInterface
{
    public function addDependencyError(string $error): void;

    /**
     * @return string[]
     */
    public function getDependencyErrors(): array;
}
