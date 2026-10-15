<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A private property with no native type and no default value, documented only with a nullable single
 * object "@var" tag (e.g. "@var IpAddress|null" or "@var ?IpAddress"), must declare the native nullable
 * type instead, e.g. "private ?IpAddress $ipAddress = null". Generic "@template" types are left alone,
 * as they cannot be expressed as a native type.
 *
 * @implements Rule<ClassLike>
 *
 * @see \Utils\PHPStan\Tests\Rule\NullableObjectPropertyMustHaveNativeTypeRuleTest
 */
final class NullableObjectPropertyMustHaveNativeTypeRule implements Rule
{
    private const SCALAR_TYPES = ['array', 'string', 'int', 'integer', 'bool', 'boolean', 'float',
        'double', 'mixed', 'object', 'callable', 'iterable', 'void', 'false', 'true', 'null',
        'self', 'static', 'parent', 'resource', 'scalar', 'list', 'never'];

    public function getNodeType(): string
    {
        return ClassLike::class;
    }

    /**
     * @param ClassLike $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classDocComment = $node->getDocComment();
        $templateNames = $this->resolveTemplateNames(null === $classDocComment ? null : $classDocComment->getText());

        $ruleErrors = [];

        foreach ($node->getProperties() as $property) {
            if (!$property->isPrivate() || $property->type instanceof Node) {
                continue;
            }

            $docComment = $property->getDocComment();
            if (null === $docComment) {
                continue;
            }

            $coreType = $this->matchNullableObjectType($docComment->getText(), $templateNames);
            if (null === $coreType) {
                continue;
            }

            foreach ($property->props as $propertyProperty) {
                // a default value means the signature is intentional, leave it alone
                if ($propertyProperty->default instanceof Node) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Property "$%s" should declare native type "?%s = null" instead of a "@var" docblock only.',
                    $propertyProperty->name->toString(),
                    $this->resolveShortClassName($coreType)
                ))
                    ->identifier('mautic.nullableObjectPropertyMustHaveNativeType')
                    ->line($propertyProperty->getStartLine())
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @return list<string>
     */
    private function resolveTemplateNames(?string $docText): array
    {
        if (null === $docText) {
            return [];
        }

        if (preg_match_all('/@template(?:-covariant|-contravariant)?\s+([A-Za-z_]\w*)/', $docText, $matches)) {
            return $matches[1];
        }

        return [];
    }

    /**
     * @param list<string> $templateNames
     */
    private function matchNullableObjectType(string $docText, array $templateNames): ?string
    {
        if (!preg_match('/@var\s+(\S+)/', $docText, $matches)) {
            return null;
        }

        $rawType = $matches[1];

        // generics, arrays, intersections and callables are not a plain nullable object
        if (preg_match('/[<>\[\]&(]/', $rawType)) {
            return null;
        }

        $hasNull = false;
        if (str_starts_with($rawType, '?')) {
            $hasNull = true;
            $rawType = substr($rawType, 1);
        }

        $core = null;
        foreach (explode('|', $rawType) as $part) {
            $part = trim($part);
            if ('' === $part) {
                continue;
            }

            if ('null' === strtolower($part)) {
                $hasNull = true;
                continue;
            }

            if (null !== $core) {
                // more than one non-null member
                return null;
            }

            $core = $part;
        }

        if (!$hasNull || null === $core) {
            return null;
        }

        $bare = ltrim($core, '\\');
        if (in_array(strtolower($bare), self::SCALAR_TYPES, true)) {
            return null;
        }

        if (in_array($bare, $templateNames, true)) {
            return null;
        }

        return $core;
    }

    private function resolveShortClassName(string $className): string
    {
        $lastSeparatorPosition = strrpos($className, '\\');
        if (false === $lastSeparatorPosition) {
            return $className;
        }

        return substr($className, $lastSeparatorPosition + 1);
    }
}
