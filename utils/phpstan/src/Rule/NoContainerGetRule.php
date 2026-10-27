<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Forbid service location via the container, e.g. $container->get('some.service').
 *
 * Pulling a service out of the container by name hides the dependency from static analysis and returns an
 * untyped object. Inject the service through the constructor as a typed property instead. A scoped
 * ServiceLocator is allowed, because it is an explicit, typed set of services rather than the whole container.
 * Migrations bootstrap services outside the DI graph, so those files are skipped.
 *
 * Tests do need to fetch services from the container, so there the call is allowed - but only with a class
 * constant, e.g. get(PathsHelper::class), which gives a typed service instead of an untyped object.
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
    private const ERROR_MESSAGE = 'Do not fetch a service from the container via ->get(...). Inject the service as a typed constructor property instead.';

    /**
     * @var string
     */
    private const TEST_ERROR_MESSAGE = 'Do not fetch a service from the container by a string name. Use a class constant, e.g. ->get(SomeService::class), to get a typed service.';

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
        if (str_contains($scope->getFile(), '/migrations/')) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (self::GET_METHOD !== $node->name->toString()) {
            return [];
        }

        if (!$this->isStaticServiceName($node)) {
            return [];
        }

        if (!$this->isContainerCaller($scope->getType($node->var))) {
            return [];
        }

        $isTestFile = $this->isTestFile($scope->getFile());

        // in tests fetching from the container is fine, as long as it is by a class constant
        if ($isTestFile && !$node->getArgs()[0]->value instanceof String_) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message($isTestFile ? self::TEST_ERROR_MESSAGE : self::ERROR_MESSAGE)
            ->identifier('mautic.noContainerGet')
            ->build();

        return [$ruleError];
    }

    /**
     * Only a hardcoded service name can be turned into a constructor dependency, e.g. get(SomeService::class) or
     * get('mautic.helper.something'). A variable name is resolved at runtime, so there is nothing to inject.
     */
    private function isStaticServiceName(MethodCall $methodCall): bool
    {
        $args = $methodCall->getArgs();
        if ([] === $args) {
            return false;
        }

        $serviceNameExpr = $args[0]->value;

        if ($serviceNameExpr instanceof String_) {
            return true;
        }

        return $serviceNameExpr instanceof ClassConstFetch
            && $serviceNameExpr->name instanceof Identifier
            && 'class' === $serviceNameExpr->name->toLowerString();
    }

    private function isContainerCaller(Type $callerType): bool
    {
        // a getContainer() call can be typed as nullable, the null part is irrelevant here
        $callerType = TypeCombinator::removeNull($callerType);

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

    private function isTestFile(string $file): bool
    {
        return str_contains($file, '/Tests/')
            || str_ends_with($file, 'Test.php')
            || str_ends_with($file, 'TestCase.php');
    }
}
