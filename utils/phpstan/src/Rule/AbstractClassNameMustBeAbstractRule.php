<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A class whose name contains "Abstract" must be declared abstract.
 *
 * @implements Rule<Class_>
 */
final class AbstractClassNameMustBeAbstractRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // anonymous class has no name
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        // a final class cannot be abstract; skip it (e.g. a "final" test named AbstractFooTest)
        if ($node->isFinal()) {
            return [];
        }

        $shortClassName = $node->name->toString();

        if (!str_contains($shortClassName, 'Abstract')) {
            return [];
        }

        if ($node->isAbstract()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Class "%s" has "Abstract" in its name but is not declared abstract. Add the "abstract" keyword or rename the class.',
            $shortClassName
        ))
            ->identifier('mautic.abstractClassName')
            ->build();

        return [$ruleError];
    }
}
