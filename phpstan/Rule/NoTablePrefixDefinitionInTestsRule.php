<?php

declare(strict_types=1);

namespace MauticPhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A test must not define the "MAUTIC_TABLE_PREFIX" const, it is already defined by the test bootstrap.
 *
 * @implements Rule<FuncCall>
 */
final class NoTablePrefixDefinitionInTestsRule implements Rule
{
    /**
     * @var string
     */
    private const TABLE_PREFIX_CONSTANT = 'MAUTIC_TABLE_PREFIX';

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!preg_match('#/Tests/.*Test\.php$#', $scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Node\Name) {
            return [];
        }

        if ('define' !== $node->name->toLowerString()) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return [];
        }

        if (!$firstArg->value instanceof String_) {
            return [];
        }

        if (self::TABLE_PREFIX_CONSTANT !== $firstArg->value->value) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Test must not define the "%s" const, the test bootstrap defines it already. Remove the definition.',
            self::TABLE_PREFIX_CONSTANT
        ))
            ->identifier('mautic.noTablePrefixDefinitionInTests')
            ->build();

        return [$ruleError];
    }
}
