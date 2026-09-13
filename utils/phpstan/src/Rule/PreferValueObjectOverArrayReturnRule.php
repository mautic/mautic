<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VoidType;

/**
 * A method that returns a keyed array of 2-3 named values packs several results into one array for the caller to
 * read by key. A small value object names each value as a typed property, so prefer one over the loose array.
 *
 * Only literal returns of 2 or 3 elements where every element has a string key are flagged - single values,
 * positional arrays and larger config/option maps are left alone. Static data maps whose values are all nested
 * arrays or constants are skipped too, as those are config/definition tables rather than packed results.
 * Test classes are skipped, as arrays there are simple fixtures. Methods overriding a parent one are skipped too,
 * as their shape is bound by the parent contract.
 *
 * @implements Rule<Return_>
 */
final readonly class PreferValueObjectOverArrayReturnRule implements Rule
{
    private const int MIN_VALUE_COUNT = 2;

    private const int MAX_VALUE_COUNT = 3;

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * @param Return_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->expr instanceof Array_) {
            return [];
        }

        if (!$scope->isInClass()) {
            return [];
        }

        // arrays in tests are simple fixtures/data providers, skip them
        if (str_ends_with($scope->getClassReflection()->getName(), 'Test')) {
            return [];
        }

        $methodReflection = $scope->getFunction();
        if (!$methodReflection instanceof MethodReflection) {
            return [];
        }

        // a method overriding a parent one is bound to that contract's shape, skip it
        if ($this->isDeclaredInParent($scope, $methodReflection->getName())) {
            return [];
        }

        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        if ($returnType instanceof VoidType) {
            return [];
        }

        // a return inside a closure/callback is attributed to the enclosing method, so skip it
        if ($scope->getAnonymousFunctionReflection() instanceof ParametersAcceptor) {
            return [];
        }

        $valueCount = count($node->expr->items);
        if ($valueCount < self::MIN_VALUE_COUNT || $valueCount > self::MAX_VALUE_COUNT) {
            return [];
        }

        if (!$this->hasStringKeyOnEveryItem($node->expr)) {
            return [];
        }

        if ($this->hasOnlyStaticDataValues($node->expr)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Method "%s()" returns a keyed array of %d values; consider a dedicated value object instead.',
            $scope->getFunction()->getName(),
            $valueCount
        ))
            ->identifier('mautic.preferValueObjectOverArrayReturn')
            ->build();

        return [$ruleError];
    }

    private function isDeclaredInParent(Scope $scope, string $methodName): bool
    {
        $parentClass = $scope->getClassReflection()->getParentClass();
        while ($parentClass instanceof ClassReflection) {
            if ($parentClass->hasMethod($methodName)) {
                return true;
            }

            $parentClass = $parentClass->getParentClass();
        }

        return false;
    }

    private function hasStringKeyOnEveryItem(Array_ $array): bool
    {
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem->key instanceof String_) {
                return false;
            }
        }

        return true;
    }

    /**
     * Static data maps - nested arrays or constant lookups - are config/definition tables, not packed results.
     */
    private function hasOnlyStaticDataValues(Array_ $array): bool
    {
        foreach ($array->items as $arrayItem) {
            $value = $arrayItem->value;
            if (!$value instanceof Array_ && !$value instanceof ClassConstFetch && !$value instanceof ConstFetch) {
                return false;
            }
        }

        return true;
    }
}
