<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A method that returns a keyed array of 2-3 named values packs several results into one array for the caller to
 * read by key. A small value object names each value as a typed property, so prefer one over the loose array.
 *
 * Only literal returns of 2 or 3 elements where every element has a string key are flagged - single values,
 * positional arrays and larger config/option maps are left alone.
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

        if (!$scope->getFunction() instanceof MethodReflection) {
            return [];
        }

        $valueCount = count($node->expr->items);
        if ($valueCount < self::MIN_VALUE_COUNT || $valueCount > self::MAX_VALUE_COUNT) {
            return [];
        }

        if (!$this->hasStringKeyOnEveryItem($node->expr)) {
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

    private function hasStringKeyOnEveryItem(Array_ $array): bool
    {
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem->key instanceof String_) {
                return false;
            }
        }

        return true;
    }
}
