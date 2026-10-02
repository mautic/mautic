<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Persistence\ObjectManager;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * An entity manager must not be asked for a repository by an entity class constant, e.g. getRepository(Lead::class).
 *
 * The returned repository has a generic type only, so the concrete repository methods are hidden from both the reader
 * and static analysis. Inject the repository as a typed constructor dependency instead. The entity manager can be a
 * property, a variable or any other expression - only its type matters.
 *
 * Tests are skipped, as fetching an entity by its repository is a legit shortcut there.
 *
 * @implements Rule<MethodCall>
 */
final class NoEntityManagerGetRepositoryRule implements Rule
{
    /**
     * @var string
     */
    private const GET_REPOSITORY_METHOD = 'getRepository';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // inside a trait the scope file is the class using it, so the very same call would be reported
        // once per using class, in a file that does not contain it
        if ($scope->isInTrait()) {
            return [];
        }

        if ($this->isTestFile($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if (self::GET_REPOSITORY_METHOD !== $node->name->toString()) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return [];
        }

        // only an exact entity constant, e.g. Lead::class
        if (!$firstArg->value instanceof ClassConstFetch) {
            return [];
        }

        if (!$firstArg->value->class instanceof Name) {
            return [];
        }

        if (!$firstArg->value->name instanceof Node\Identifier || 'class' !== $firstArg->value->name->toString()) {
            return [];
        }

        // the caller must be an entity manager, a property, a variable or any other expression alike
        $callerType = $scope->getType($node->var);
        if (!(new ObjectType(ObjectManager::class))->isSuperTypeOf($callerType)->yes()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Entity manager must not fetch the "%s" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.',
            $firstArg->value->class->toString()
        ))
            ->identifier('mautic.noEntityManagerGetRepository')
            ->build();

        return [$ruleError];
    }

    private function isTestFile(string $filePath): bool
    {
        return str_contains($filePath, '/Tests/') || str_ends_with($filePath, 'Test.php') || str_ends_with($filePath, 'TestCase.php');
    }
}
