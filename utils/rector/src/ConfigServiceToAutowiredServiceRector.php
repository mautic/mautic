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
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Parser\SimplePhpParser;
use Rector\PhpParser\Printer\BetterStandardPrinter;
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
 * Both sides of the move happen in a single run, triggered by config.php alone. The new $services->set() lines
 * are written into services.php right here, as text, before the definitions are dropped from the config.php AST -
 * a service can never end up removed from config.php without being registered in services.php first.
 * Letting Rector print services.php instead would split the move over 2 runs, and the 2nd one would be
 * served from cache, as config.php stays untouched in the 1st one.
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
        private readonly BetterStandardPrinter $betterStandardPrinter,
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
        if (!$node instanceof Return_ || !$node->expr instanceof Array_) {
            return null;
        }

        return $this->refactorConfigFile($node, $node->expr);
    }

    private function refactorConfigFile(Return_ $return, Array_ $configArray): ?Return_
    {
        $servicesFilePath = dirname($this->getFile()->getFilePath()).'/services.php';
        if (!file_exists($servicesFilePath)) {
            return null;
        }

        $servicesFileContent = file_get_contents($servicesFilePath);
        if (!is_string($servicesFileContent)) {
            return null;
        }

        $servicesClosure = $this->matchServicesClosure($servicesFileContent);
        if (!$servicesClosure instanceof Closure) {
            return null;
        }

        $servicesArray = $this->matchArrayValueByKey($configArray, 'services');
        if (!$servicesArray instanceof Array_) {
            return null;
        }

        $movableServices = $this->resolveMovableServices($servicesArray);
        if ([] === $movableServices) {
            return null;
        }

        $registeredServiceNames = $this->resolveRegisteredServiceNames($servicesClosure);

        $setStmts = [];
        foreach ($movableServices as [, , $serviceName, $serviceDefinition]) {
            if (in_array($serviceName, $registeredServiceNames, true)) {
                continue;
            }

            $setStmts[] = $this->createServiceSetStmt($serviceName, $serviceDefinition);
        }

        // the services must be registered in services.php first, or the move would drop them
        if ([] !== $setStmts && !$this->registerServices($servicesFilePath, $servicesFileContent, $servicesClosure, $setStmts)) {
            return null;
        }

        $changedGroupArrays = [];

        foreach ($movableServices as [$groupArray, $key]) {
            unset($groupArray->items[$key]);
            $changedGroupArrays[spl_object_id($groupArray)] = $groupArray;
        }

        foreach ($changedGroupArrays as $changedGroupArray) {
            $changedGroupArray->items = array_values($changedGroupArray->items);
        }

        $this->removeEmptyGroups($servicesArray, $changedGroupArrays);
        $this->removeEmptyServices($configArray, $servicesArray);

        return $return;
    }

    /**
     * A group that lost its very last service, e.g. 'membership' => [], is of no use anymore.
     *
     * @param array<int, Array_> $changedGroupArrays
     */
    private function removeEmptyGroups(Array_ $servicesArray, array $changedGroupArrays): void
    {
        $hasChanged = false;

        foreach ($servicesArray->items as $key => $groupArrayItem) {
            $groupArray = $groupArrayItem->value;
            if (!$groupArray instanceof Array_) {
                continue;
            }

            if ([] !== $groupArray->items || !isset($changedGroupArrays[spl_object_id($groupArray)])) {
                continue;
            }

            unset($servicesArray->items[$key]);
            $hasChanged = true;
        }

        if ($hasChanged) {
            $servicesArray->items = array_values($servicesArray->items);
        }
    }

    /**
     * The whole 'services' key goes once its very last group is gone.
     */
    private function removeEmptyServices(Array_ $configArray, Array_ $servicesArray): void
    {
        if ([] !== $servicesArray->items) {
            return;
        }

        foreach ($configArray->items as $key => $configArrayItem) {
            if ($configArrayItem->value !== $servicesArray) {
                continue;
            }

            unset($configArray->items[$key]);
            $configArray->items = array_values($configArray->items);

            return;
        }
    }

    /**
     * @return array<array{Array_, int|string, string, ServiceDefinition}> group array, item key, service name and definition
     */
    private function resolveMovableServices(Array_ $servicesArray): array
    {
        $movableServices = [];

        foreach ($servicesArray->items as $groupArrayItem) {
            $groupArray = $groupArrayItem->value;
            if (!$groupArray instanceof Array_) {
                continue;
            }

            foreach ($groupArray->items as $key => $serviceArrayItem) {
                $serviceDefinition = $this->matchServiceDefinition($serviceArrayItem);
                if (!$serviceDefinition instanceof ServiceDefinition) {
                    continue;
                }

                $serviceName = $serviceArrayItem->key;
                if (!$serviceName instanceof String_) {
                    continue;
                }

                $movableServices[] = [$groupArray, $key, $serviceName->value, $serviceDefinition];
            }
        }

        return $movableServices;
    }

    /**
     * Writes the new $services->set() lines into services.php as text, to keep the rest of the file formatting
     * untouched. A dry run only checks that the write would be possible.
     *
     * @param Expression[] $setStmts
     */
    private function registerServices(string $servicesFilePath, string $servicesFileContent, Closure $servicesClosure, array $setStmts): bool
    {
        $updatedServicesFileContent = $this->createUpdatedServicesFileContent($servicesFileContent, $servicesClosure, $setStmts);
        if (null === $updatedServicesFileContent) {
            return false;
        }

        if ($this->isDryRun()) {
            return true;
        }

        return false !== file_put_contents($servicesFilePath, $updatedServicesFileContent);
    }

    /**
     * @param Expression[] $setStmts
     *
     * @return string|null null when there is no statement to append the new services to
     */
    private function createUpdatedServicesFileContent(string $servicesFileContent, Closure $servicesClosure, array $setStmts): ?string
    {
        $anchorStmt = $this->resolveAnchorStmt($servicesClosure);
        if (!$anchorStmt instanceof Stmt) {
            return null;
        }

        $anchorEndPosition = $anchorStmt->getEndFilePos();
        if ($anchorEndPosition < 0) {
            return null;
        }

        $indentation   = $this->resolveIndentation($servicesFileContent, $anchorStmt->getStartFilePos());
        $appendContent = '';

        foreach ($setStmts as $setStmt) {
            $appendContent .= "\n".$indentation.$this->betterStandardPrinter->print([$setStmt]);
        }

        return substr($servicesFileContent, 0, $anchorEndPosition + 1)
            .$appendContent
            .substr($servicesFileContent, $anchorEndPosition + 1);
    }

    /**
     * The new services go right after the last $services->load(...) call, to keep them above aliases.
     */
    private function resolveAnchorStmt(Closure $closure): ?Stmt
    {
        $anchorStmt = null;

        foreach ($closure->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            $loadMethodCall = $this->betterNodeFinder->findFirst(
                $stmt->expr,
                fn (Node $node): bool => $node instanceof MethodCall && $this->isName($node->name, 'load')
            );

            if ($loadMethodCall instanceof MethodCall) {
                $anchorStmt = $stmt;
            }
        }

        if ($anchorStmt instanceof Stmt) {
            return $anchorStmt;
        }

        // no load() call, the services still have to land inside the closure
        $lastStmt = $closure->stmts[count($closure->stmts) - 1] ?? null;

        return $lastStmt instanceof Stmt ? $lastStmt : null;
    }

    private function resolveIndentation(string $fileContent, int $startFilePos): string
    {
        if ($startFilePos < 0) {
            return '';
        }

        $lineStartPosition = strrpos(substr($fileContent, 0, $startFilePos), "\n");
        if (false === $lineStartPosition) {
            return '';
        }

        $indentation = substr($fileContent, $lineStartPosition + 1, $startFilePos - $lineStartPosition - 1);

        return '' === trim($indentation) ? $indentation : '';
    }

    /**
     * The --dry-run option is mirrored to the parallel workers, so plain argv is enough to spot it.
     */
    private function isDryRun(): bool
    {
        $argv = (array) ($_SERVER['argv'] ?? []);

        return in_array('--dry-run', $argv, true) || in_array('-n', $argv, true);
    }

    private function matchServicesClosure(string $servicesFileContent): ?Closure
    {
        $stmts = $this->simplePhpParser->parseString($servicesFileContent);

        // the file is parsed on its own, so the "use" imports have to be resolved by hand
        $nodeTraverser = new NodeTraverser(new NameResolver());
        $stmts         = $nodeTraverser->traverse($stmts);

        $return = $this->betterNodeFinder->findFirstInstanceOf($stmts, Return_::class);
        if (!$return instanceof Return_ || !$return->expr instanceof Closure) {
            return null;
        }

        $closure = $return->expr;
        if (!$this->isContainerConfiguratorClosure($closure)) {
            return null;
        }

        return $this->hasServicesVariable($closure) ? $closure : null;
    }

    /**
     * @return string[] service names already registered in services.php
     */
    private function resolveRegisteredServiceNames(Closure $servicesClosure): array
    {
        $setMethodCalls = $this->betterNodeFinder->find(
            $servicesClosure->stmts,
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
}
