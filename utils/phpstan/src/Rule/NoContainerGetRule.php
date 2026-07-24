<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbid service location via $container->get(...) and $this->get(...).
 *
 * Pulling a service out of the container by name hides the dependency from static analysis and returns an
 * untyped object. Inject the service through the constructor as a typed property instead. Tests may still use
 * the container to bootstrap services, so test files are skipped.
 *
 * @implements Rule<MethodCall>
 */
final class NoContainerGetRule implements Rule
{
    /**
     * @var string
     */
    private const GET_METHOD = 'get';

    /**
     * @var string[]
     */
    private const CONTAINER_VARIABLE_NAMES = ['this', 'container'];

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
        if ($this->isTestFile($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (self::GET_METHOD !== $node->name->toString()) {
            return [];
        }

        if (!$node->var instanceof Variable || !is_string($node->var->name)) {
            return [];
        }

        if (!in_array($node->var->name, self::CONTAINER_VARIABLE_NAMES, true)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Do not fetch a service via $%s->get(...). Inject the service as a typed constructor property instead.',
            $node->var->name
        ))
            ->identifier('mautic.noContainerGet')
            ->nonIgnorable()
            ->build();

        return [$ruleError];
    }

    private function isTestFile(string $file): bool
    {
        return str_contains($file, '/Tests/')
            || str_ends_with($file, 'Test.php')
            || str_ends_with($file, 'TestCase.php');
    }
}
