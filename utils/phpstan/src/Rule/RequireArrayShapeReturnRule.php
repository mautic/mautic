<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * A method that returns a keyed array of 2-3 named values should declare that shape in its @return, so the caller
 * knows each key and its type instead of reading an opaque array.
 *
 * Only methods whose every value-return is a literal keyed array of 2 or 3 string-keyed elements are flagged - the
 * clean "packed result" case where a single shape can describe the return. Methods that also return a plain value,
 * a variable, a call result or a general array are left alone, as a keyed shape cannot represent those and PHPStan
 * would collapse it anyway. Single values, positional arrays and larger config/option maps are left alone, and so
 * are static data maps whose values are all nested arrays or constants. A @return that already declares an array
 * shape, a void or mixed return, anonymous classes and methods overriding a parent contract are skipped too.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class RequireArrayShapeReturnRule implements Rule
{
    private const int MIN_VALUE_COUNT = 2;

    private const int MAX_VALUE_COUNT = 3;

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->stmts === null) {
            return [];
        }

        if (!$scope->isInClass()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        // anonymous classes are local one-off implementations, skip them
        if ($classReflection->isAnonymous()) {
            return [];
        }

        $methodName = $node->name->toString();

        // a method overriding a parent one is bound to that contract's shape, skip it
        if ($this->isDeclaredInParent($classReflection, $methodName)) {
            return [];
        }

        $returnType = $classReflection->getNativeMethod($methodName)->getVariants()[0]->getReturnType();
        if ($returnType->isVoid()->yes()) {
            return [];
        }

        // a mixed return cannot be pinned to an array shape, skip it
        if ($returnType instanceof MixedType) {
            return [];
        }

        // @return already declares an array shape, the keys and types are documented
        if ($this->declaresArrayShape($returnType)) {
            return [];
        }

        $valueReturns = array_filter(
            $this->collectReturns($node->stmts),
            static fn (Return_ $return): bool => $return->expr instanceof Node
        );
        if ($valueReturns === []) {
            return [];
        }

        $firstKeyedArray = null;
        foreach ($valueReturns as $valueReturn) {
            $expr = $valueReturn->expr;

            // a return that is not a packed keyed array means no single shape fits, skip the method
            if (!$expr instanceof Array_ || !$this->isPackedKeyedArray($expr)) {
                return [];
            }

            $firstKeyedArray ??= $expr;
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Method "%s()" returns a keyed array of %d values; declare its shape in @return, e.g. array{key: type}.',
            $methodName,
            count($firstKeyedArray->items)
        ))
            ->identifier('mautic.requireArrayShapeReturn')
            ->line($firstKeyedArray->getStartLine())
            ->build();

        return [$ruleError];
    }

    private function isPackedKeyedArray(Array_ $expr): bool
    {
        $valueCount = count($expr->items);
        if ($valueCount < self::MIN_VALUE_COUNT || $valueCount > self::MAX_VALUE_COUNT) {
            return false;
        }

        if (!$this->hasStringKeyOnEveryItem($expr)) {
            return false;
        }

        return !$this->hasOnlyStaticDataValues($expr);
    }

    /**
     * Collect return statements in the given nodes, without descending into nested functions or classes.
     *
     * @param Node[] $nodes
     *
     * @return list<Return_>
     */
    private function collectReturns(array $nodes): array
    {
        $returns = [];
        foreach ($nodes as $node) {
            if ($node instanceof Return_) {
                $returns[] = $node;
                continue;
            }

            // nested closures, functions and anonymous classes have their own return context
            if ($node instanceof FunctionLike || $node instanceof Class_) {
                continue;
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $child = $node->{$subNodeName};
                if ($child instanceof Node) {
                    $returns = [...$returns, ...$this->collectReturns([$child])];
                } elseif (is_array($child)) {
                    $returns = [...$returns, ...$this->collectReturns(array_filter($child, static fn ($item): bool => $item instanceof Node))];
                }
            }
        }

        return $returns;
    }

    // an array shape may be one member of a union (e.g. array{...}|null), so check each member
    private function declaresArrayShape(Type $type): bool
    {
        $types = $type instanceof UnionType ? $type->getTypes() : [$type];
        foreach ($types as $innerType) {
            if ($innerType->isConstantArray()->yes()) {
                return true;
            }
        }

        return false;
    }

    private function isDeclaredInParent(ClassReflection $classReflection, string $methodName): bool
    {
        $parentClass = $classReflection->getParentClass();
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
