<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports the service() arguments of Config/services.php that only repeat the type autowiring resolves anyway,
 * e.g. a service that passes the very class its constructor type hints:
 *
 *     final class MenuHelper
 *     {
 *         public function __construct(private RequestStack $requestStack) {}
 *     }
 *
 *     $services->set(MenuHelper::class)
 *         ->args([service(RequestStack::class)]);
 *
 *     $services->set(MenuHelper::class);
 *
 * With autowiring on, the container injects the RequestStack by its type, so the explicit argument brings
 * nothing. Only the safe shapes are reported, the ones a plain removal keeps working:
 *
 *   - a whole args([...]) call in which every argument is a service(Type::class) matching the constructor
 *     parameter of the same position - the call goes and every parameter is autowired instead;
 *   - a named arg('$name', service(Type::class)) call matching the type of its parameter - it goes on its own.
 *
 * A positional argument only some of which are redundant is left alone: dropping one would shift the rest,
 * and an argument passing another type than the parameter, e.g. a func_get_args() extra, is no autowiring
 * duplicate at all. A parameter type hinting an interface a concrete service is passed for is left alone too,
 * autowiring the interface needs a binding the argument stands in for.
 *
 * @implements Rule<MethodCall>
 */
final readonly class NoRedundantAutowiredServiceArgumentRule implements Rule
{
    private const string SERVICES_FILE_NAME = 'services.php';

    private const string SERVICE_FUNCTION = 'Symfony\Component\DependencyInjection\Loader\Configurator\service';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

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
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->toString();
        if ('args' !== $methodName && 'arg' !== $methodName) {
            return [];
        }

        $parameters = $this->resolveConstructorParameters($node);
        if (null === $parameters) {
            return [];
        }

        if ('arg' === $methodName) {
            return $this->processNamedArgument($node, $parameters);
        }

        return $this->processArgumentList($node, $parameters);
    }

    /**
     * @param list<ParameterReflection> $parameters
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processArgumentList(MethodCall $node, array $parameters): array
    {
        $args = $node->getArgs();
        if (1 !== count($args) || !$args[0]->value instanceof Array_) {
            return [];
        }

        $items = $args[0]->value->items;
        if ([] === $items || count($items) > count($parameters)) {
            return [];
        }

        foreach ($items as $position => $item) {
            // a keyed element is a named argument, not the plain positional list this rule reasons about
            if (null !== $item->key) {
                return [];
            }

            $serviceClass = $this->matchServiceClass($item->value);
            if (null === $serviceClass || !$this->isSameType($parameters[$position], $serviceClass)) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message('Every argument only repeats the type autowiring injects, remove the args() call and let autowiring resolve the constructor.')
                ->identifier('mautic.noRedundantAutowiredServiceArgument')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * @param list<ParameterReflection> $parameters
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processNamedArgument(MethodCall $node, array $parameters): array
    {
        $args = $node->getArgs();
        if (2 !== count($args) || !$args[0]->value instanceof String_) {
            return [];
        }

        $parameterName = ltrim($args[0]->value->value, '$');

        $serviceClass = $this->matchServiceClass($args[1]->value);
        if (null === $serviceClass) {
            return [];
        }

        foreach ($parameters as $parameter) {
            if ($parameter->getName() !== $parameterName) {
                continue;
            }

            if (!$this->isSameType($parameter, $serviceClass)) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Argument "$%s" only repeats the type autowiring injects, remove the arg() call and let autowiring resolve "%s".',
                    $parameterName,
                    $serviceClass
                ))
                    ->identifier('mautic.noRedundantAutowiredServiceArgument')
                    ->line($node->getStartLine())
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * The constructor parameters of the class the set() call up the chain registers, null when there is no
     * such class, no constructor or an autowire() call switching autowiring off.
     *
     * @return list<ParameterReflection>|null
     */
    private function resolveConstructorParameters(MethodCall $node): ?array
    {
        $className = null;

        for ($cursor = $node->var; $cursor instanceof MethodCall; $cursor = $cursor->var) {
            if (!$cursor->name instanceof Identifier) {
                continue;
            }

            // a per-service autowire() call may switch autowiring off, then the argument is not redundant
            if ('autowire' === $cursor->name->toString()) {
                return null;
            }

            if ('set' === $cursor->name->toString()) {
                $className = $this->matchSetClassName($cursor);
            }
        }

        if (null === $className || !$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstructor()) {
            return null;
        }

        return $classReflection->getConstructor()->getVariants()[0]->getParameters();
    }

    private function matchSetClassName(MethodCall $node): ?string
    {
        $args = $node->getArgs();

        // set(Foo::class) names the class first, set('mautic.some.id', Foo::class) names it second
        if (1 === count($args)) {
            return $this->matchClassName($args[0]->value);
        }

        if (2 === count($args)) {
            return $this->matchClassName($args[1]->value);
        }

        return null;
    }

    private function matchServiceClass(Node $node): ?string
    {
        if (!$node instanceof FuncCall || !$node->name instanceof Name) {
            return null;
        }

        $functionName = $node->name->toString();
        if ('service' !== $functionName && self::SERVICE_FUNCTION !== $functionName) {
            return null;
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (null === $firstArg) {
            return null;
        }

        return $this->matchClassName($firstArg->value);
    }

    private function matchClassName(Node $node): ?string
    {
        if (!$node instanceof ClassConstFetch || !$node->class instanceof Name) {
            return null;
        }

        if (!$node->name instanceof Identifier || 'class' !== $node->name->toLowerString()) {
            return null;
        }

        return $node->class->toString();
    }

    private function isSameType(ParameterReflection $parameter, string $serviceClass): bool
    {
        $parameterType = $parameter->getType();

        return $parameterType->isObject()->yes() && [$serviceClass] === $parameterType->getObjectClassNames();
    }
}
