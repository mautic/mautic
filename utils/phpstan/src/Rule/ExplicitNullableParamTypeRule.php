<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A parameter typed "Type" with a "null" default is implicitly nullable, which PHP 8.4 deprecates. Write the
 * nullability out as "?Type".
 *
 * The type is already present, so this only makes the existing nullability explicit: it changes no caller and
 * no override, it just drops the deprecation.
 *
 * @implements Rule<Param>
 *
 * @see \Utils\PHPStan\Tests\Rule\ExplicitNullableParamTypeRuleTest
 */
final class ExplicitNullableParamTypeRule implements Rule
{
    public function getNodeType(): string
    {
        return Param::class;
    }

    /**
     * @param Param $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $type = $node->type;
        if (null === $type) {
            return [];
        }

        // already explicit: "?Type", "Type|null" or a DNF type that cannot take a leading "?"
        if ($type instanceof NullableType || $type instanceof UnionType || $type instanceof IntersectionType) {
            return [];
        }

        if (!$type instanceof Identifier && !$type instanceof Node\Name) {
            return [];
        }

        if (!$node->default instanceof ConstFetch || 'null' !== $node->default->name->toLowerString()) {
            return [];
        }

        $typeName = $type->toString();

        // "?mixed" is not a valid type, mixed already includes null
        if ('mixed' === strtolower($typeName)) {
            return [];
        }

        $parameterName = $node->var instanceof Node\Expr\Variable && is_string($node->var->name)
            ? $node->var->name
            : 'value';

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Parameter "$%s" is implicitly nullable. Declare it as "?%s".',
            $parameterName,
            $typeName
        ))
            ->identifier('mautic.explicitNullableParamType')
            ->line($node->getStartLine())
            ->build();

        return [$ruleError];
    }
}
