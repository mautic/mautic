<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Parser\SimplePhpParser;
use Rector\Rector\AbstractRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\Rector\ValueObject\ServiceDefinition;
use Utils\Rector\ValueObject\ServiceTag;

/**
 * Moves plain service definitions from bundle Config/config.php to the autowired Config/services.php next to it.
 *
 * Only "class", "arguments", "tag", "tags" and "tagArguments" definitions are moved:
 *
 *   'mautic.some.service' => ['class' => SomeService::class, 'tag' => 'security.voter']
 *   ->  $services->set('mautic.some.service', SomeService::class)->tag('security.voter');
 *
 * The manual "arguments" are dropped, but only for a class whose constructor autowiring can fill on its own -
 * a single scalar or array argument, e.g. a "%mautic.some_config%" parameter, keeps the whole definition in config.php.
 *
 * The tags are kept as ->tag() calls, "tags" pairing with "tagArguments" by index, just like ServicePass does.
 *
 * Definitions with "alias", "parent", "factory", ... are left in place,
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
                if (!$this->matchServiceDefinition($serviceArrayItem) instanceof ServiceDefinition) {
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

        $serviceDefinitionsByName = $this->resolveMovableServices($configFilePath);
        if ([] === $serviceDefinitionsByName) {
            return null;
        }

        if (!$this->hasServicesVariable($closure)) {
            return null;
        }

        $setStmts = [];

        foreach ($serviceDefinitionsByName as $serviceName => $serviceDefinition) {
            if ($this->hasServiceSet($closure, $serviceName)) {
                continue;
            }

            $setStmts[] = $this->createServiceSetStmt($serviceName, $serviceDefinition);
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
     * @return array<string, ServiceDefinition> service name to definition
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

        $serviceDefinitionsByName = [];

        foreach ($servicesArray->items as $groupArrayItem) {
            $groupArray = $groupArrayItem->value;
            if (!$groupArray instanceof Array_) {
                continue;
            }

            foreach ($groupArray->items as $serviceArrayItem) {
                $serviceDefinition = $this->matchServiceDefinition($serviceArrayItem);
                if (!$serviceDefinition instanceof ServiceDefinition) {
                    continue;
                }

                $serviceName = $serviceArrayItem->key;
                if (!$serviceName instanceof String_) {
                    continue;
                }

                $serviceDefinitionsByName[$serviceName->value] = $serviceDefinition;
            }
        }

        return $serviceDefinitionsByName;
    }

    /**
     * Matches a service definition made of a "class" key and optional "arguments", "tag", "tags" and "tagArguments",
     * e.g. ['class' => SomeService::class, 'arguments' => ['translator'], 'tag' => 'security.voter'].
     */
    private function matchServiceDefinition(ArrayItem $arrayItem): ?ServiceDefinition
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

        // "alias", "parent", "factory", ... would be silently dropped on the way
        if ([] !== array_diff($definitionKeys, ['class', 'arguments', 'tag', 'tags', 'tagArguments'])) {
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

        $serviceTags = $this->matchServiceTags($definitionArray);
        if (null === $serviceTags) {
            return null;
        }

        return new ServiceDefinition($className->value, $serviceTags);
    }

    /**
     * @return ServiceTag[]|null null when the tags cannot be turned into ->tag() calls
     */
    private function matchServiceTags(Array_ $definitionArray): ?array
    {
        $tagArgumentsValue = $this->matchArrayValueByKey($definitionArray, 'tagArguments');
        if (null !== $tagArgumentsValue && !$tagArgumentsValue instanceof Array_) {
            return null;
        }

        $tagsValue = $this->matchArrayValueByKey($definitionArray, 'tags');

        // a single "tag" takes the whole "tagArguments" as its arguments
        if (!$tagsValue instanceof Array_) {
            $tagValue = $this->matchArrayValueByKey($definitionArray, 'tag');
            if (null === $tagValue) {
                return null !== $tagArgumentsValue ? null : [];
            }

            if (!$tagValue instanceof String_) {
                return null;
            }

            $tagArguments = $this->createTagArguments($tagArgumentsValue);
            if (!$tagArguments instanceof Array_) {
                return null;
            }

            return [new ServiceTag($tagValue->value, $tagArguments)];
        }

        // "tags" pair with "tagArguments" by index, see ServicePass
        $serviceTags = [];

        foreach ($tagsValue->items as $key => $tagArrayItem) {
            if (!$tagArrayItem->value instanceof String_) {
                return null;
            }

            $tagArgumentsItemValue = $tagArgumentsValue instanceof Array_
                ? ($tagArgumentsValue->items[$key]->value ?? null)
                : null;

            if (null !== $tagArgumentsItemValue && !$tagArgumentsItemValue instanceof Array_) {
                return null;
            }

            $tagArguments = $this->createTagArguments($tagArgumentsItemValue);
            if (!$tagArguments instanceof Array_) {
                return null;
            }

            $serviceTags[] = new ServiceTag($tagArrayItem->value->value, $tagArguments);
        }

        return $serviceTags;
    }

    /**
     * Re-creates the tag arguments as fresh nodes, as the parsed ones belong to another file.
     * Missing arguments end up as an empty array, null means they cannot be re-created.
     */
    private function createTagArguments(?Array_ $tagArgumentsArray): ?Array_
    {
        if (!$tagArgumentsArray instanceof Array_) {
            return new Array_([]);
        }

        $arrayItems = [];

        foreach ($tagArgumentsArray->items as $tagArgumentArrayItem) {
            if (!$tagArgumentArrayItem->key instanceof String_) {
                return null;
            }

            $tagArgumentValue = $tagArgumentArrayItem->value;
            if ($tagArgumentValue instanceof String_) {
                $tagArgumentValue = new String_($tagArgumentValue->value);
            } elseif ($tagArgumentValue instanceof Int_) {
                $tagArgumentValue = new Int_($tagArgumentValue->value);
            } elseif ($tagArgumentValue instanceof UnaryMinus && $tagArgumentValue->expr instanceof Int_) {
                $tagArgumentValue = new UnaryMinus(new Int_($tagArgumentValue->expr->value));
            } elseif ($tagArgumentValue instanceof ConstFetch) {
                $tagArgumentValue = new ConstFetch(new Name($tagArgumentValue->name->toString()));
            } else {
                return null;
            }

            $arrayItems[] = new ArrayItem($tagArgumentValue, new String_($tagArgumentArrayItem->key->value));
        }

        return new Array_($arrayItems);
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

    private function createServiceSetStmt(string $serviceName, ServiceDefinition $serviceDefinition): Expression
    {
        $methodCall = new MethodCall(new Variable(self::SERVICES_VARIABLE_NAME), 'set', [
            new Arg(new String_($serviceName)),
            new Arg(new ClassConstFetch(new Name($serviceDefinition->getClassName()), 'class')),
        ]);

        foreach ($serviceDefinition->getServiceTags() as $serviceTag) {
            $args = [new Arg(new String_($serviceTag->getName()))];

            if ($serviceTag->hasArguments()) {
                $args[] = new Arg($serviceTag->getArguments());
            }

            $methodCall = new MethodCall($methodCall, 'tag', $args);
        }

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
