<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Every public method on a "*Repository" class must declare a return type.
 *
 * A repository is a data-access contract; a missing return type hides what a query method hands back and drops
 * static checks at every call site. Magic methods (e.g. "__construct") are skipped, as PHP forbids a return type
 * on them.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\RepositoryMethodMustHaveReturnTypeRuleTest
 */
final readonly class RepositoryMethodMustHaveReturnTypeRule implements Rule
{
    private const string REPOSITORY_SUFFIX = 'Repository';

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
        if (!$node->isPublic()) {
            return [];
        }

        // magic methods like "__construct" cannot declare a return type
        if (str_starts_with($node->name->toString(), '__')) {
            return [];
        }

        if (null !== $node->returnType) {
            return [];
        }

        // inside a trait the scope class is the repository using it, so the method would be checked repeatedly
        if ($scope->isInTrait()) {
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

        if (!str_ends_with($classReflection->getName(), self::REPOSITORY_SUFFIX)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Public method "%s()" of repository class must declare a return type.',
            $node->name->toString()
        ))
            ->identifier('mautic.repositoryMethodMustHaveReturnType')
            ->line($node->getStartLine())
            ->build();

        return [$ruleError];
    }
}
