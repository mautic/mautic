<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that creates a read-only generated (virtual) column.
 */
final class GeneratedType extends Type
{
    public const string GENERATED = 'generated';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        // Must be defined in `columnDefinition` option when adding the column in the subscriber
        return '';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return null;
    }
}
