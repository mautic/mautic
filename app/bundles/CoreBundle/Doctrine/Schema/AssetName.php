<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Schema\Name;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\NamedObject;

/**
 * Reads the plain name of a schema object.
 *
 * DBAL 4 deprecated AbstractAsset::getName() in favour of NamedObject::getObjectName(),
 * which returns a Name rather than a string. Name::toString() is not a substitute: it
 * renders a quoted identifier with its quotes, so it does not always match the plain
 * column or table name Mautic compares against. The identifier's value is taken instead.
 *
 * Tables carry an optionally qualified name (schema plus table); only the unqualified
 * part is wanted here, which is what getName() returned.
 */
final class AssetName
{
    public static function of(NamedObject $object): string
    {
        return self::fromName($object->getObjectName());
    }

    public static function fromName(Name $name): string
    {
        if ($name instanceof OptionallyQualifiedName) {
            return $name->getUnqualifiedName()->getValue();
        }

        if ($name instanceof UnqualifiedName) {
            return $name->getIdentifier()->getValue();
        }

        return $name->toString();
    }
}
