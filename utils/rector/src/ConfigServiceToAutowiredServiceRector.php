<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Parser\SimplePhpParser;
use Rector\Rector\AbstractRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Moves plain service definitions from bundle Config/config.php to the autowired Config/services.php next to it.
 *
 * Only "class" and "arguments" definitions are moved, as autowiring covers both:
 *
 *   'mautic.some.service' => ['class' => SomeService::class]  ->  $services->set('mautic.some.service', SomeService::class);
 *
 * The manual "arguments" are dropped, but only for a class whose constructor autowiring can fill on its own -
 * a single scalar or array argument, e.g. a "%mautic.some_config%" parameter, keeps the whole definition in config.php.
 *
 * Definitions with "tag", "alias", "parent", "factory", ... are left in place,
 * as moving them would silently drop their configuration.
 *
 * The move takes 2 runs on purpose - the 1st one registers the service in services.php,
 * the 2nd one drops it from config.php. That way a service can never end up removed
 * from config.php without being registered in services.php first.
 */
final class ConfigServiceToAutowiredServiceRector extends AbstractRector
{
    /**
     * @var string
     */
    private const SERVICES_VARIABLE_NAME = 'services';

    public function __construct(
        private readonly SimplePhpParser $simplePhpParser,
        private readonly BetterNodeFinder $betterNodeFinder,
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Return_) {
            return null;
        }

        if ($node->expr instanceof Array_) {
            return $this->refactorConfigFile($node, $node->expr);
        }

        if ($node->expr instanceof Closure) {
            return $this->refactorServicesFile($node, $node->expr);
        }

        return null;
    }

    private function refactorConfigFile(Return_ $return, Array_ $configArray): ?Return_
    {
        // the service is only dropped here once services.php registers it, never in the same run;
        // Rector prints file by file, so services.php might not be updated yet
        $registeredServiceNames = $this->resolveRegisteredServiceNames(
            dirname($this->getFile()->getFilePath()).'/services.php'
        );

        if ([] === $registeredServiceNames) {
            return null;
        }

        $servicesArray = $this->matchArrayValueByKey($configArray, 'services');
        if (!$servicesArray instanceof Array_) {
            return null;
        }

        $hasChanged = false;

        foreach ($servicesArray->items as $groupArrayItem) {
            $groupArray = $groupArrayItem->value;
            if (!$groupArray instanceof Array_) {
                continue;
            }

            $hasGroupChanged = false;

            foreach ($groupArray->items as $key => $serviceArrayItem) {
                if (!$this->matchServiceClass($serviceArrayItem) instanceof String_) {
                    continue;
                }

                $serviceName = $serviceArrayItem->key;
                if (!$serviceName instanceof String_ || !in_array($serviceName->value, $registeredServiceNames, true)) {
                    continue;
                }

                unset($groupArray->items[$key]);
                $hasGroupChanged = true;
            }

            if ($hasGroupChanged) {
                $groupArray->items = array_values($groupArray->items);
                $hasChanged = true;
            }
        }

        if (!$hasChanged) {
            return null;
        }

        return $return;
    }

    private function refactorServicesFile(Return_ $return, Closure $closure): ?Return_
    {
        if (!$this->isContainerConfiguratorClosure($closure)) {
            return null;
        }

        $configFilePath = dirname($this->getFile()->getFilePath()).'/config.php';
        if (!file_exists($configFilePath)) {
            return null;
        }

        $serviceClassesByName = $this->resolveMovableServices($configFilePath);
        if ([] === $serviceClassesByName) {
            return null;
        }

        if (!$this->hasServicesVariable($closure)) {
            return null;
        }

        $setStmts = [];

        foreach ($serviceClassesByName as $serviceName => $className) {
            if ($this->hasServiceSet($closure, $serviceName)) {
                continue;
            }

            $setStmts[] = $this->createServiceSetStmt($serviceName, $className);
        }

        if ([] === $setStmts) {
            return null;
        }

        $insertPosition = $this->resolveInsertPosition($closure);
        array_splice($closure->stmts, $insertPosition, 0, $setStmts);

        return $return;
    }

    /**
     * @return string[] service names already registered in services.php
     */
    private function resolveRegisteredServiceNames(string $servicesFilePath): array
    {
        if (!file_exists($servicesFilePath)) {
            return [];
        }

        $stmts = $this->simplePhpParser->parseFile($servicesFilePath);

        $setMethodCalls = $this->betterNodeFinder->find(
            $stmts,
            fn (Node $node): bool => $node instanceof MethodCall && $this->isName($node->name, 'set')
        );

        $serviceNames = [];

        foreach ($setMethodCalls as $setMethodCall) {
            if (!$setMethodCall instanceof MethodCall) {
                continue;
            }

            $firstArg = $setMethodCall->getArgs()[0] ?? null;
            if (!$firstArg instanceof Arg || !$firstArg->value instanceof String_) {
                continue;
            }

            $serviceNames[] = $firstArg->value->value;
        }

        return $serviceNames;
    }

    /**
     * @return array<string, string> service name to class name
     */
    private function resolveMovableServices(string $configFilePath): array
    {
        $stmts = $this->simplePhpParser->parseFile($configFilePath);

        $return = $this->betterNodeFinder->findFirstInstanceOf($stmts, Return_::class);
        if (!$return instanceof Return_ || !$return->expr instanceof Array_) {
            return [];
        }

        $servicesArray = $this->matchArrayValueByKey($return->expr, 'services');
        if (!$servicesArray instanceof Array_) {
            return [];
        }

        $serviceClassesByName = [];

        foreach ($servicesArray->items as $groupArrayItem) {
            $groupArray = $groupArrayItem->value;
            if (!$groupArray instanceof Array_) {
                continue;
            }

            foreach ($groupArray->items as $serviceArrayItem) {
                $className = $this->matchServiceClass($serviceArrayItem);
                if (!$className instanceof String_) {
                    continue;
                }

                $serviceName = $serviceArrayItem->key;
                if (!$serviceName instanceof String_) {
                    continue;
                }

                $serviceClassesByName[$serviceName->value] = $className->value;
            }
        }

        return $serviceClassesByName;
    }

    /**
     * Matches a service definition made of a "class" key and optional "arguments",
     * e.g. ['class' => SomeService::class] or ['class' => SomeService::class, 'arguments' => ['translator']].
     */
    private function matchServiceClass(ArrayItem $arrayItem): ?String_
    {
        if (!$arrayItem->key instanceof String_) {
            return null;
        }

        // 3rd party class overrides, e.g. "oneup_uploader.controller.dropzone.class"; those are not autowirable
        if (str_ends_with($arrayItem->key->value, '.class')) {
            return null;
        }

        $definitionArray = $arrayItem->value;
        if (!$definitionArray instanceof Array_) {
            return null;
        }

        $definitionKeys = [];
        foreach ($definitionArray->items as $definitionArrayItem) {
            if (!$definitionArrayItem->key instanceof String_) {
                return null;
            }

            $definitionKeys[] = $definitionArrayItem->key->value;
        }

        // "tag", "alias", "parent", "factory", ... would be silently dropped on the way
        if ([] !== array_diff($definitionKeys, ['class', 'arguments'])) {
            return null;
        }

        $classValue = $this->matchArrayValueByKey($definitionArray, 'class');
        $className   = $this->matchClassName($classValue);
        if (!$className instanceof String_) {
            return null;
        }

        // manual arguments are only dropped when autowiring can fill every constructor argument on its own
        if (in_array('arguments', $definitionKeys, true) && !$this->isAutowirableClass($className->value)) {
            return null;
        }

        return $className;
    }

    private function matchClassName(?Node $classValue): ?String_
    {
        if ($classValue instanceof String_) {
            return $classValue;
        }

        if (!$classValue instanceof ClassConstFetch || !$classValue->class instanceof Name) {
            return null;
        }

        if (!$this->isName($classValue->name, 'class')) {
            return null;
        }

        return new String_($classValue->class->toString());
    }

    private function isAutowirableClass(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstructor()) {
            return true;
        }

        $parametersAcceptor = $classReflection->getConstructor()->getVariants()[0] ?? null;
        if (null === $parametersAcceptor) {
            return false;
        }

        foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
            if ($parameterReflection->getDefaultValue() instanceof Type) {
                continue;
            }

            // scalars, arrays and parameters like "%mautic.some_config%" have to stay wired by hand
            if (!$parameterReflection->getType()->isObject()->yes()) {
                return false;
            }
        }

        return true;
    }

    private function matchArrayValueByKey(Array_ $array, string $keyName): ?Node
    {
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem->key instanceof String_) {
                continue;
            }

            if ($arrayItem->key->value !== $keyName) {
                continue;
            }

            return $arrayItem->value;
        }

        return null;
    }

    private function isContainerConfiguratorClosure(Closure $closure): bool
    {
        $firstParam = $closure->params[0] ?? null;
        if (!$firstParam instanceof Param) {
            return false;
        }

        if (!$firstParam->type instanceof Name) {
            return false;
        }

        return $this->isName($firstParam->type, ContainerConfigurator::class);
    }

    private function hasServicesVariable(Closure $closure): bool
    {
        $variable = $this->betterNodeFinder->findFirst(
            $closure->stmts,
            fn (Node $node): bool => $node instanceof Variable && $this->isName($node, self::SERVICES_VARIABLE_NAME)
        );

        return $variable instanceof Variable;
    }

    private function hasServiceSet(Closure $closure, string $serviceName): bool
    {
        $methodCall = $this->betterNodeFinder->findFirst(
            $closure->stmts,
            function (Node $node) use ($serviceName): bool {
                if (!$node instanceof MethodCall || !$this->isName($node->name, 'set')) {
                    return false;
                }

                $firstArg = $node->getArgs()[0] ?? null;

                return $firstArg instanceof Arg
                    && $firstArg->value instanceof String_
                    && $firstArg->value->value === $serviceName;
            }
        );

        return $methodCall instanceof MethodCall;
    }

    private function createServiceSetStmt(string $serviceName, string $className): Expression
    {
        $methodCall = new MethodCall(new Variable(self::SERVICES_VARIABLE_NAME), 'set', [
            new Arg(new String_($serviceName)),
            new Arg(new ClassConstFetch(new Name($className), 'class')),
        ]);

        return new Expression($methodCall);
    }

    /**
     * Puts the new services right after the last $services->load(...) call, to keep them above aliases.
     */
    private function resolveInsertPosition(Closure $closure): int
    {
        $insertPosition = count($closure->stmts);

        foreach ($closure->stmts as $key => $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            $loadMethodCall = $this->betterNodeFinder->findFirst(
                $stmt->expr,
                fn (Node $node): bool => $node instanceof MethodCall && $this->isName($node->name, 'load')
            );

            if ($loadMethodCall instanceof MethodCall) {
                $insertPosition = $key + 1;
            }
        }

        return $insertPosition;
    }
}
