<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the class names used as parameter types, e.g. "SomeHelper" of __construct(SomeHelper $someHelper).
 * A class name alias is there for autowiring, so a type hint of that very class is what makes it used.
 *
 * @implements Collector<Param, list<string>>
 */
final class ServiceTypeUsageCollector implements Collector
{
    public function getNodeType(): string
    {
        return Param::class;
    }

    /**
     * @return list<string>|null the class names of the parameter type
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (null === $node->type) {
            return null;
        }

        $classNames = $this->resolveTypeClassNames($node->type);

        return [] === $classNames ? null : $classNames;
    }

    /**
     * @return list<string>
     */
    private function resolveTypeClassNames(Identifier|Name|ComplexType $type): array
    {
        if ($type instanceof Name) {
            return [$type->toString()];
        }

        if ($type instanceof NullableType) {
            return $this->resolveTypeClassNames($type->type);
        }

        if (!$type instanceof UnionType && !$type instanceof IntersectionType) {
            return [];
        }

        $classNames = [];
        foreach ($type->types as $innerType) {
            $classNames = array_merge($classNames, $this->resolveTypeClassNames($innerType));
        }

        return $classNames;
    }
}
