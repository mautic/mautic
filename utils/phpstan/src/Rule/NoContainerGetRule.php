<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Forbid service location via the container, e.g. $container->get('some.service').
 *
 * Pulling a service out of the container by name hides the dependency from static analysis and returns an
 * untyped object. Inject the service through the constructor as a typed property instead. A scoped
 * ServiceLocator is allowed, because it is an explicit, typed set of services rather than the whole container.
 * Tests and migrations bootstrap services outside the DI graph, so those files are skipped.
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
     * @var string
     */
    private const SERVICE_LOCATOR_TYPE = 'Symfony\Component\DependencyInjection\ServiceLocator';

    /**
     * @var string[]
     */
    private const CONTAINER_TYPES = [
        'Psr\Container\ContainerInterface',
        'Symfony\Component\DependencyInjection\ContainerInterface',
    ];

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
        if ($this->isExemptFile($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (self::GET_METHOD !== $node->name->toString()) {
            return [];
        }

        if (!$this->isContainerCaller($scope->getType($node->var))) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(
            'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.'
        )
            ->identifier('mautic.noContainerGet')
            ->build();

        return [$ruleError];
    }

    private function isContainerCaller(Type $callerType): bool
    {
        // a scoped ServiceLocator is an allowed, explicit set of services
        if ((new ObjectType(self::SERVICE_LOCATOR_TYPE))->isSuperTypeOf($callerType)->yes()) {
            return false;
        }

        foreach (self::CONTAINER_TYPES as $containerType) {
            if ((new ObjectType($containerType))->isSuperTypeOf($callerType)->yes()) {
                return true;
            }
        }

        return false;
    }

    private function isExemptFile(string $file): bool
    {
        return str_contains($file, '/Tests/')
            || str_contains($file, '/migrations/')
            || str_ends_with($file, 'Test.php')
            || str_ends_with($file, 'TestCase.php');
    }
}
