<?php

namespace Mautic\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps a PHP array to a clob SQL type.
 *
 * DBAL 4 removed \Doctrine\DBAL\Types\ArrayType, so the serialization the
 * parent used to provide is implemented here.
 *
 * @since 2.0
 */
final class ArrayType extends Type
{
    /**
     * DBAL 4 removed Doctrine's Types::ARRAY along with the built-in type; entity mappings
     * still refer to the 'array' type name this class is registered under.
     */
    public const string ARRAY = 'array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (!is_array($value)) {
            return (null === $value) ? 'N;' : 'a:0:{}';
        }

        $serialized = serialize($value);

        if (str_contains($serialized, chr(0))) {
            $serialized = str_replace("\0", '__NULL_BYTE__', $serialized);
            throw new ConversionException('Serialized array includes null-byte. This cannot be saved as a text. Please check if you not provided object with protected or private members. Serialized Array: '.$serialized);
        }

        return $serialized;
    }

    /**
     * @return array<mixed>
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        try {
            $value = $this->unserializeValue($value);

            if (!is_array($value) || (1 > count($value))) {
                return $value;
            }

            foreach ($value as $key => $element) {
                if (!is_object($element)) {
                    continue;
                }

                $reflectionObject     = new \ReflectionObject($element);
                $reflectionProperties = $reflectionObject->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);

                // Let's check if $value contains objects with private or protected members.
                // If it contains such objects we have to remove them from $array.
                // This will "heal" the database. There must be no null bytes.
                if (0 < count($reflectionProperties)) {
                    unset($value[$key]);
                }
            }

            return $value;
        } catch (ConversionException|\ErrorException) {
            return [];
        }
    }

    /**
     * Mirrors the unserialization \Doctrine\DBAL\Types\ArrayType performed before it was
     * removed in DBAL 4, including turning unserialize() notices into exceptions.
     */
    private function unserializeValue(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        set_error_handler(static function (int $code, string $message): bool {
            throw new ConversionException('Could not convert database value to PHP array: '.$message);
        });

        try {
            // Objects are unserialized rather than rejected, as DBAL did: convertToPHPValue
            // drops the ones that would carry null bytes and keeps the rest
            return unserialize((string) $value);
        } finally {
            restore_error_handler();
        }
    }
}
