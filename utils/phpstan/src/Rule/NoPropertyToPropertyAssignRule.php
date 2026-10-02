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
 * An object property must not be assigned from another object property of the same object.
 *
 * "$this->repository = $this->someRepository;" keeps the very same service under 2 names, so both properties have to be
 * kept in sync forever. Use the original property directly instead and drop the duplicate one.
 *
 * Only object properties are reported - a scalar or array property is often a deliberate snapshot of a previous state,
 * e.g. "$this->bodyInitial = $this->body;". Anonymous classes are skipped, as they are local one-offs.
 *
 * @implements Rule<Assign>
 */
final class NoPropertyToPropertyAssignRule implements Rule
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
        // an anonymous class is a local one-off, e.g. a test double filling a parent property
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection || $classReflection->isAnonymous()) {
            return [];
        }

        $assignedPropertyName = $this->matchThisPropertyName($node->var);
        if (null === $assignedPropertyName) {
            return [];
        }

        $sourcePropertyName = $this->matchThisPropertyName($node->expr);
        if (null === $sourcePropertyName) {
            return [];
        }

        // "$this->items = $this->items" is a different smell, not a duplicated property
        if ($assignedPropertyName === $sourcePropertyName) {
            return [];
        }

        // a scalar or array property is often a deliberate snapshot of a previous state
        if ([] === $scope->getType($node->expr)->getObjectClassNames()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Property "$this->%s" must not be assigned from property "$this->%s". Use the original property directly instead.',
            $assignedPropertyName,
            $sourcePropertyName
        ))
            ->identifier('mautic.noPropertyToPropertyAssign')
            ->build();

        return [$ruleError];
    }

    /**
     * Returns the property name of a "$this->someProperty" fetch, null for anything else.
     */
    private function matchThisPropertyName(Node\Expr $expr): ?string
    {
        if (!$expr instanceof PropertyFetch) {
            return null;
        }

        if (!$expr->var instanceof Variable || 'this' !== $expr->var->name) {
            return null;
        }

        if (!$expr->name instanceof Node\Identifier) {
            return null;
        }

        return $expr->name->toString();
    }
}
