<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A repository must not fetch another repository by an entity class constant, e.g. getRepository(Lead::class).
 *
 * A repository already knows its own entity, so reaching for getRepository(Some::class) inside it hides a
 * cross-repository dependency behind the entity manager. Inject the target repository as a typed property
 * instead, to make the dependency and its type explicit.
 *
 * @implements Rule<MethodCall>
 */
final class NoGetRepositoryWithEntityInRepositoryRule implements Rule
{
    /**
     * @var string
     */
    private const GET_REPOSITORY_METHOD = 'getRepository';

    /**
     * @var string
     */
    private const REPOSITORY_SUFFIX = 'Repository.php';

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
        // inside a trait the scope file is the repository using it, so the very same call would be reported
        // once per repository, in a file that does not contain it
        if ($scope->isInTrait()) {
            return [];
        }

        if (!str_ends_with($scope->getFile(), self::REPOSITORY_SUFFIX)) {
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

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Repository must not fetch the "%s" repository by entity constant. Inject the repository as a typed property instead, to make the dependency and its type explicit.',
            $firstArg->value->class->toString()
        ))
            ->identifier('mautic.noGetRepositoryWithEntityInRepository')
            ->nonIgnorable()
            ->build();

        return [$ruleError];
    }
}
