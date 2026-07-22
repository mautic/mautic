<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A test must not copy an object property into a local variable, use the property directly instead.
 *
 * @implements Rule<Assign>
 */
final class NoObjectPropertyAssignToVariableInTestsRule implements Rule
{
    public function getNodeType(): string
    {
        return Assign::class;
    }

    /**
     * @param Assign $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // must be test case file
        if (!str_ends_with($scope->getFile(), 'Test.php') && !str_ends_with($scope->getFile(), 'TestCase.php')) {
            return [];
        }

        if (!$node->var instanceof Variable) {
            return [];
        }

        if (!$node->expr instanceof PropertyFetch) {
            return [];
        }

        if (!$node->expr->var instanceof Variable) {
            return [];
        }

        if ('this' !== $node->expr->var->name) {
            return [];
        }

        if (!$node->expr->name instanceof Node\Identifier) {
            return [];
        }

        $propertyType = $scope->getType($node->expr);
        if ([] === $propertyType->getObjectClassNames()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Test must not assign the object property "$this->%s" to a variable. Use the property directly instead.',
            $node->expr->name->toString()
        ))
            ->identifier('mautic.noObjectPropertyAssignToVariableInTests')
            ->build();

        return [$ruleError];
    }
}
