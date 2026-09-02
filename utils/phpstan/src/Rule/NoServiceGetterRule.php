<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A service must be injected, not reached through a public getter.
 *
 * Exposing "getQueueRepository(): WebhookQueueRepository { return $this->webhookQueueRepository; }" turns the owning
 * class into a service locator: callers pull the repository out of it instead of asking for the repository directly.
 * The dependency is hidden and the getter has to be maintained for every service the class holds. Inject the service
 * where it is used instead.
 *
 * Matched: a public, non-static get*() method whose only statement is "return $this->property;" and whose return type
 * is a service class (by suffix - Repository, Model, Manager, Factory, Provider, Service, Helper, Handler).
 *
 * @implements Rule<ClassMethod>
 */
final class NoServiceGetterRule implements Rule
{
    /**
     * @var string
     */
    private const GET_PREFIX = 'get';

    /**
     * Class-name suffixes that mark a service. A getter returning one of these hands out an injectable dependency.
     *
     * @var string[]
     */
    private const SERVICE_SUFFIXES = [
        'Repository',
        'Model',
        'Manager',
        'Factory',
        'Provider',
        'Service',
        'Helper',
        'Handler',
    ];

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
        if (!$node->isPublic() || $node->isStatic()) {
            return [];
        }

        if (!str_starts_with($node->name->toLowerString(), self::GET_PREFIX)) {
            return [];
        }

        $returnClassName = $this->matchServiceReturnType($node->returnType, $scope);
        if (null === $returnClassName) {
            return [];
        }

        if (!$this->isPropertyReturnBody($node)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Public getter "%s()" returns service "%s". Inject the service where it is used instead of exposing it.',
            $node->name->toString(),
            $returnClassName
        ))
            ->identifier('mautic.noServiceGetter')
            ->build();

        return [$ruleError];
    }

    /**
     * Returns the resolved class name when the return type is a service class, null otherwise.
     */
    private function matchServiceReturnType(?Node $returnType, Scope $scope): ?string
    {
        if ($returnType instanceof NullableType) {
            $returnType = $returnType->type;
        }

        if (!$returnType instanceof Name) {
            return null;
        }

        $className = $scope->resolveName($returnType);

        $shortName = str_contains($className, '\\')
            ? substr($className, strrpos($className, '\\') + 1)
            : $className;

        foreach (self::SERVICE_SUFFIXES as $serviceSuffix) {
            if (str_ends_with($shortName, $serviceSuffix)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * True when the method body is exactly "return $this->property;".
     */
    private function isPropertyReturnBody(ClassMethod $classMethod): bool
    {
        if (null === $classMethod->stmts || 1 !== count($classMethod->stmts)) {
            return false;
        }

        $onlyStmt = $classMethod->stmts[0];
        if (!$onlyStmt instanceof Return_) {
            return false;
        }

        $returnedExpr = $onlyStmt->expr;
        if (!$returnedExpr instanceof PropertyFetch) {
            return false;
        }

        if (!$returnedExpr->var instanceof Variable || 'this' !== $returnedExpr->var->name) {
            return false;
        }

        return $returnedExpr->name instanceof Identifier;
    }
}
