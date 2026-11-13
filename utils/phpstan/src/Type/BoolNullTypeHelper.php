<?php

declare(strict_types=1);

namespace Utils\PHPStan\Type;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class BoolNullTypeHelper
{
    public static function isBoolOrNull(Type $type): bool
    {
        if (!TypeCombinator::containsNull($type)) {
            return false;
        }

        $withoutNull = TypeCombinator::removeNull($type);

        return !$withoutNull instanceof NeverType && $withoutNull->isBoolean()->yes();
    }

    /**
     * Native type as sorted "|"-joined lowercase names ("?bool" → "bool|null"), or null when missing or "mixed".
     */
    public static function printNativeType(?Node $type): ?string
    {
        if (!$type instanceof Node) {
            return null;
        }

        $names = self::collectNames($type);
        if (['mixed'] === $names) {
            return null;
        }

        sort($names);

        return implode('|', array_unique($names));
    }

    /**
     * @return string[]
     */
    private static function collectNames(Node $type): array
    {
        if ($type instanceof NullableType) {
            return [...self::collectNames($type->type), 'null'];
        }

        if ($type instanceof UnionType) {
            return array_merge(...array_map(self::collectNames(...), $type->types));
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return [strtolower($type->toString())];
        }

        // an intersection or DNF type is never bool-compatible
        return $type instanceof ComplexType ? ['complex'] : ['unknown'];
    }
}
