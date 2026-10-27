<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * This trait provides default implementation of OptimisticLockInterface.
 */
trait OptimisticLockTrait
{
    private int $version = OptimisticLockInterface::INITIAL_VERSION;

    private ?int $currentVersion = null;

    private bool $incrementVersion = false;

    public function getVersion(): int
    {
        return $this->currentVersion ?? $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->currentVersion   = $version;
        $this->incrementVersion = false;
    }

    public function isMarkedForVersionIncrement(): bool
    {
        return $this->incrementVersion;
    }

    public function markForVersionIncrement(): void
    {
        $this->incrementVersion = true;
    }

    public function getVersionField(): string
    {
        return 'version';
    }

    private static function addVersionField(ClassMetadataBuilder $builder): void
    {
        $builder->createField('version', Types::INTEGER)
            ->columnName('version')
            ->option('default', OptimisticLockInterface::INITIAL_VERSION)
            ->option('unsigned', true)
            ->build();
    }
}
