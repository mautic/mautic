<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * A single transaction-scoped mutex for the shared username/email namespace.
 */
final class UserCreationLock
{
    public const TABLE_NAME = 'user_creation_lock';

    private int $id = 1;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable(self::TABLE_NAME);
        $builder->createField('id', 'integer')->makePrimaryKey()->build();
    }

    public function getId(): int
    {
        return $this->id;
    }
}
