<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Parser\SimplePhpParser;
use Rector\PhpParser\Printer\BetterStandardPrinter;
use Rector\Rector\AbstractRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Utils\Rector\ValueObject\ServiceArgument;
use Utils\Rector\ValueObject\ServiceDefinition;
use Utils\Rector\ValueObject\ServiceMethodCall;
use Utils\Rector\ValueObject\ServiceTag;

/**
 * Moves plain service definitions from bundle Config/config.php to the autowired Config/services.php next to it.
 *
 * Only "class", "arguments", "tag", "tags" and "tagArguments" definitions are moved:
 *
 *   'mautic.some.service' => ['class' => SomeService::class, 'tag' => 'security.voter']
 *   ->  $services->set('mautic.some.service', SomeService::class)->tag('security.voter');
 *
 * The manual "arguments" are dropped for every object-typed constructor parameter, as autowiring fills those
 * on its own. What is left over - a scalar or a "%mautic.some_config%" parameter - is written as an explicit
 * ->arg() call, named after the constructor parameter:
 *
 *   'mautic.some.service' => ['class' => SomeService::class, 'arguments' => ['translator', '%mautic.some_config%']]
 *   ->  $services->set('mautic.some.service', SomeService::class)->arg('$someConfig', param('mautic.some_config'));
 *
 * The argument values follow ServicePass, see Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass:
 * "%some.parameter%" becomes param(), a class name string stays a string, a quoted "some string" loses its quotes
 * and anything else is a service reference. A "@=" expression keeps the whole definition in config.php.
 *
 * The tags are kept as ->tag() calls, "tags" pairing with "tagArguments" by index, just like ServicePass does.
 * A service of a group ServicePass tags on its own, e.g. "permissions", is given that tag explicitly,
 * see GROUP_DEFAULT_TAGS.
 *
 * The "alias" is no service alias at all, ServicePass hands it over as a tag argument, so it becomes one here too:
 *
 *   'mautic.some.validator' => ['class' => SomeValidator::class, 'tag' => 'validator.constraint_validator', 'alias' => 'some_validator']
 *   ->  $services->set(...)->tag('validator.constraint_validator', ['alias' => 'some_validator']);
 *
 * The "methodCalls" become ->call() calls, their arguments following the very same rules as the constructor ones:
 *
 *   'mautic.some.helper' => ['class' => SomeHelper::class, 'methodCalls' => ['setFormModel' => ['mautic.form.model.form']]]
 *   ->  $services->set(...)->call('setFormModel', [service('mautic.form.model.form')]);
 *
 * Definitions with "parent", "factory", ... are left in place,
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

    /**
     * @var string
     */
    private const SERVICE_FUNCTION_NAME = 'service';

    /**
     * @var string
     */
    private const PARAM_FUNCTION_NAME = 'param';

    /**
     * @var string
     */
    private const CONFIGURATOR_NAMESPACE = 'Symfony\Component\DependencyInjection\Loader\Configurator';

    /**
     * The default tags ServicePass gives a whole group of services, in the tags the moved service would lose.
     *
     * The other groups keep their default tag through autoconfigure(), e.g. "events" tags every
     * EventSubscriberInterface with "kernel.event_subscriber" on its own.
     *
     * @var array<string, string>
     */
    private const GROUP_DEFAULT_TAGS = [
        'helpers'     => 'twig.helper',
        'models'      => 'mautic.model',
        'permissions' => 'mautic.permissions',
    ];

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

            // ServicePass gives every config.php service an alias of its very class name,
            // see Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass
            if ($serviceName !== $serviceDefinition->getClassName()) {
                $setStmts[] = $this->createServiceAliasStmt($serviceName, $serviceDefinition->getClassName());
            }
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

            $groupName = $groupArrayItem->key instanceof String_ ? $groupArrayItem->key->value : '';

            foreach ($groupArray->items as $key => $serviceArrayItem) {
                $serviceDefinition = $this->matchServiceDefinition($serviceArrayItem, $groupName);
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
            $appendContent .= "\n".$indentation.$this->wrapPrintedStmt($this->betterStandardPrinter->print([$setStmt]), $indentation);
        }

        $updatedServicesFileContent = substr($servicesFileContent, 0, $anchorEndPosition + 1)
            .$appendContent
            .substr($servicesFileContent, $anchorEndPosition + 1);

        // the imports go last, so the anchor position stays valid while the services are appended
        return $this->addConfiguratorImports($updatedServicesFileContent, $setStmts);
    }

    /**
     * A wired service carries enough calls to outgrow its line, so each of them gets one of its own.
     */
    private function wrapPrintedStmt(string $printedStmt, string $indentation): string
    {
        if (!str_contains($printedStmt, '->arg(') && !str_contains($printedStmt, '->call(')) {
            return $printedStmt;
        }

        return str_replace(
            ['->arg(', '->call(', '->tag('],
            ["\n".$indentation.'    ->arg(', "\n".$indentation.'    ->call(', "\n".$indentation.'    ->tag('],
            $printedStmt
        );
    }

    /**
     * The printed service() and param() calls are unqualified, so services.php has to import them.
     *
     * @param Expression[] $setStmts
     */
    private function addConfiguratorImports(string $servicesFileContent, array $setStmts): string
    {
        $funcCalls = $this->betterNodeFinder->find(
            $setStmts,
            fn (Node $node): bool => $node instanceof FuncCall
                && $this->isNames($node->name, [self::SERVICE_FUNCTION_NAME, self::PARAM_FUNCTION_NAME])
        );

        $functionNames = [];
        foreach ($funcCalls as $funcCall) {
            if ($funcCall instanceof FuncCall && $funcCall->name instanceof Name) {
                $functionNames[$funcCall->name->toString()] = true;
            }
        }

        $functionNames = array_keys($functionNames);
        sort($functionNames);

        foreach ($functionNames as $functionName) {
            $useStatement = 'use function '.self::CONFIGURATOR_NAMESPACE.'\\'.$functionName.';';

            if (str_contains($servicesFileContent, $useStatement)) {
                continue;
            }

            $servicesFileContent = $this->insertUseStatement($servicesFileContent, $useStatement);
        }

        return $servicesFileContent;
    }

    /**
     * A new import joins the function imports, in alphabetical order,
     * or opens their very own block right below the class ones.
     */
    private function insertUseStatement(string $servicesFileContent, string $useStatement): string
    {
        $functionUseStatements = $this->matchUseStatements($servicesFileContent, '#^use function [^;]+;$#m');

        foreach ($functionUseStatements as [$matchedUseStatement, $matchPosition]) {
            if (strcmp($matchedUseStatement, $useStatement) < 0) {
                continue;
            }

            return substr($servicesFileContent, 0, $matchPosition)
                .$useStatement."\n"
                .substr($servicesFileContent, $matchPosition);
        }

        // below the last import of the block the new one belongs to
        $blocks = [
            ["\n", $functionUseStatements],
            ["\n\n", $this->matchUseStatements($servicesFileContent, '#^use [^;]+;$#m')],
            ["\n\n", $this->matchUseStatements($servicesFileContent, '#^(?:declare\(strict_types=1\);|<\?php)$#m')],
        ];

        foreach ($blocks as [$separator, $useStatements]) {
            if ([] === $useStatements) {
                continue;
            }

            [$lastUseStatement, $lastPosition] = $useStatements[count($useStatements) - 1];
            $insertPosition                    = $lastPosition + strlen($lastUseStatement);

            return substr($servicesFileContent, 0, $insertPosition)
                .$separator.$useStatement
                .substr($servicesFileContent, $insertPosition);
        }

        return $servicesFileContent;
    }

    /**
     * @return array<int, array{string, int}> matched statements and their positions, in the order they appear
     */
    private function matchUseStatements(string $servicesFileContent, string $pattern): array
    {
        if (0 === preg_match_all($pattern, $servicesFileContent, $matches, \PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $useStatements = [];
        foreach ($matches[0] as [$matchedUseStatement, $matchPosition]) {
            $useStatements[] = [$matchedUseStatement, $matchPosition];
        }

        return $useStatements;
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
     * Matches a service definition made of a "class" key and optional "arguments", "tag", "tags", "tagArguments",
     * "alias" and "methodCalls",
     * e.g. ['class' => SomeService::class, 'arguments' => ['translator'], 'tag' => 'security.voter'].
     */
    private function matchServiceDefinition(ArrayItem $arrayItem, string $groupName): ?ServiceDefinition
    {
        if (!$arrayItem->key instanceof String_) {
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

        // "parent", "factory", ... would be silently dropped on the way
        if ([] !== array_diff($definitionKeys, ['class', 'arguments', 'tag', 'tags', 'tagArguments', 'alias', 'methodCalls'])) {
            return null;
        }

        $classValue = $this->matchArrayValueByKey($definitionArray, 'class');
        $className   = $this->matchClassName($classValue);
        if (!$className instanceof String_) {
            return null;
        }

        // the moved service is autowired, whatever autowiring cannot fill becomes an explicit ->arg() call
        $serviceArguments = $this->resolveServiceArguments($className->value, $this->matchArrayValueByKey($definitionArray, 'arguments'));
        if (null === $serviceArguments) {
            return null;
        }

        $serviceTags = $this->matchServiceTags($definitionArray, $groupName);
        if (null === $serviceTags) {
            return null;
        }

        $aliasValue = $this->matchArrayValueByKey($definitionArray, 'alias');
        if (null !== $aliasValue) {
            if (!$aliasValue instanceof String_) {
                return null;
            }

            $serviceTags = $this->addTagAlias($serviceTags, $aliasValue->value);
        }

        $serviceMethodCalls = $this->resolveServiceMethodCalls($this->matchArrayValueByKey($definitionArray, 'methodCalls'));
        if (null === $serviceMethodCalls) {
            return null;
        }

        return new ServiceDefinition($className->value, $serviceArguments, $serviceTags, $serviceMethodCalls);
    }

    /**
     * The "alias" is no service alias, ServicePass hands it over as an argument of every tag the service carries,
     * see ServicePass::process(). A service without a tag has nothing to carry it, so the alias goes,
     * just like it does today.
     *
     * @param ServiceTag[] $serviceTags
     *
     * @return ServiceTag[]
     */
    private function addTagAlias(array $serviceTags, string $alias): array
    {
        $aliasedServiceTags = [];

        foreach ($serviceTags as $serviceTag) {
            $arrayItems = [];

            foreach ($serviceTag->getArguments()->items as $tagArgumentArrayItem) {
                // the "alias" wins over a "tagArguments" one of the very same name, see ServicePass
                if ($tagArgumentArrayItem->key instanceof String_ && 'alias' === $tagArgumentArrayItem->key->value) {
                    continue;
                }

                $arrayItems[] = $tagArgumentArrayItem;
            }

            $arrayItems[] = new ArrayItem(new String_($alias), new String_('alias'));

            $aliasedServiceTags[] = new ServiceTag($serviceTag->getName(), new Array_($arrayItems));
        }

        return $aliasedServiceTags;
    }

    /**
     * The "methodCalls" are keyed by the method name, their arguments a positional list, see ServicePass.
     *
     * @return ServiceMethodCall[]|null null when a call cannot be re-created
     */
    private function resolveServiceMethodCalls(?Node $methodCallsValue): ?array
    {
        if (null === $methodCallsValue) {
            return [];
        }

        if (!$methodCallsValue instanceof Array_) {
            return null;
        }

        $serviceMethodCalls = [];

        foreach ($methodCallsValue->items as $methodCallArrayItem) {
            if (!$methodCallArrayItem->key instanceof String_) {
                return null;
            }

            $argumentValues = $this->resolveArgumentValues($methodCallArrayItem->value);
            if (null === $argumentValues) {
                return null;
            }

            $arrayItems = [];

            foreach ($argumentValues as $argumentValue) {
                $argumentExpr = $this->createArgumentExpr($argumentValue);
                if (!$argumentExpr instanceof Expr) {
                    return null;
                }

                $arrayItems[] = new ArrayItem($argumentExpr);
            }

            $serviceMethodCalls[] = new ServiceMethodCall($methodCallArrayItem->key->value, new Array_($arrayItems));
        }

        return $serviceMethodCalls;
    }

    /**
     * @return ServiceTag[]|null null when the tags cannot be turned into ->tag() calls
     */
    private function matchServiceTags(Array_ $definitionArray, string $groupName): ?array
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
                if (null !== $tagArgumentsValue) {
                    return null;
                }

                // the group tags its services on its own, so the moved one has to say the tag out loud
                $groupDefaultTag = self::GROUP_DEFAULT_TAGS[$groupName] ?? null;

                return null === $groupDefaultTag ? [] : [new ServiceTag(new String_($groupDefaultTag), new Array_([]))];
            }

            $tagName = $this->createTagNameExpr($tagValue);
            if (!$tagName instanceof Expr) {
                return null;
            }

            $tagArguments = $this->createTagArguments($tagArgumentsValue);
            if (!$tagArguments instanceof Array_) {
                return null;
            }

            return [new ServiceTag($tagName, $tagArguments)];
        }

        // "tags" pair with "tagArguments" by index, see ServicePass
        $serviceTags = [];

        foreach ($tagsValue->items as $key => $tagArrayItem) {
            $tagName = $this->createTagNameExpr($tagArrayItem->value);
            if (!$tagName instanceof Expr) {
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

            $serviceTags[] = new ServiceTag($tagName, $tagArguments);
        }

        return $serviceTags;
    }

    /**
     * A tag is named by a plain string or by a class constant, e.g. FixturesCompilerPass::FIXTURE_TAG.
     */
    private function createTagNameExpr(Node $tagValue): ?Expr
    {
        if ($tagValue instanceof String_) {
            return new String_($tagValue->value);
        }

        if (!$tagValue instanceof ClassConstFetch || !$tagValue->class instanceof Name || !$tagValue->name instanceof Identifier) {
            return null;
        }

        // the class name itself would be no tag name at all
        if ($this->isName($tagValue->name, 'class')) {
            return null;
        }

        return new ClassConstFetch(new Name($tagValue->class->toString()), $tagValue->name->toString());
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

    /**
     * Pairs the manual "arguments" with the constructor parameters, by position, just like ServicePass does.
     *
     * @return ServiceArgument[]|null the arguments autowiring cannot fill, null when the service has to stay in config.php
     */
    private function resolveServiceArguments(string $className, ?Node $argumentsValue): ?array
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstructor()) {
            return [];
        }

        $parametersAcceptor = $classReflection->getConstructor()->getVariants()[0] ?? null;
        if (null === $parametersAcceptor) {
            return null;
        }

        $argumentValues = $this->resolveArgumentValues($argumentsValue);
        if (null === $argumentValues) {
            return null;
        }

        $serviceArguments = [];

        foreach ($parametersAcceptor->getParameters() as $position => $parameterReflection) {
            if ($this->isAutowirableParameter($parameterReflection)) {
                continue;
            }

            $argumentValue = $argumentValues[$position] ?? null;
            if (null === $argumentValue) {
                // nothing to wire, the parameter falls back to its own default value
                if ($parameterReflection->getDefaultValue() instanceof Type) {
                    continue;
                }

                return null;
            }

            $argumentExpr = $this->createArgumentExpr($argumentValue);
            if (!$argumentExpr instanceof Expr) {
                return null;
            }

            $serviceArguments[] = new ServiceArgument('$'.$parameterReflection->getName(), $argumentExpr);
        }

        return $serviceArguments;
    }

    private function isAutowirableParameter(ParameterReflection $parameterReflection): bool
    {
        // scalars, arrays and parameters like "%mautic.some_config%" have to be wired by hand
        return $parameterReflection->getType()->isObject()->yes();
    }

    /**
     * The "arguments" are a positional list, a lonely value counts as a list of one, see ServicePass.
     *
     * @return array<int, Node>|null null when the list is keyed, so the positions cannot be trusted
     */
    private function resolveArgumentValues(?Node $argumentsValue): ?array
    {
        if (null === $argumentsValue) {
            return [];
        }

        if (!$argumentsValue instanceof Array_) {
            return [$argumentsValue];
        }

        $argumentValues = [];

        foreach ($argumentsValue->items as $argumentArrayItem) {
            if (null !== $argumentArrayItem->key) {
                return null;
            }

            $argumentValues[] = $argumentArrayItem->value;
        }

        return $argumentValues;
    }

    /**
     * Re-creates a config.php argument as a services.php one, following ServicePass::processArgument().
     * Nodes are built from scratch, as the parsed ones belong to another file.
     */
    private function createArgumentExpr(Node $argumentValue): ?Expr
    {
        if ($argumentValue instanceof String_) {
            return $this->createStringArgumentExpr($argumentValue->value);
        }

        // a class constant, e.g. SomeClass::class, ends up as a plain class name string
        if ($argumentValue instanceof ClassConstFetch) {
            $className = $this->matchClassName($argumentValue);

            return $className instanceof String_ ? new ClassConstFetch(new Name($className->value), 'class') : null;
        }

        if ($argumentValue instanceof Int_) {
            return new Int_($argumentValue->value);
        }

        if ($argumentValue instanceof UnaryMinus && $argumentValue->expr instanceof Int_) {
            return new UnaryMinus(new Int_($argumentValue->expr->value));
        }

        if ($argumentValue instanceof ConstFetch) {
            return new ConstFetch(new Name($argumentValue->name->toString()));
        }

        return null;
    }

    private function createStringArgumentExpr(string $argumentValue): ?Expr
    {
        // "" is filled in during compilation, there is no telling with what
        if ('' === $argumentValue) {
            return null;
        }

        if (str_starts_with($argumentValue, '%') && str_ends_with($argumentValue, '%') && strlen($argumentValue) > 2) {
            $parameterName = str_replace('%%', '%', substr($argumentValue, 1, -1));

            return $this->createConfiguratorFuncCall(self::PARAM_FUNCTION_NAME, $parameterName);
        }

        // a class name stays a string, e.g. a form type passed to a factory
        if (str_contains($argumentValue, '\\')) {
            return new String_($argumentValue);
        }

        if (str_starts_with($argumentValue, '"')) {
            return new String_(substr($argumentValue, 1, -1));
        }

        // an expression would need expr(), which brings its own compilation rules along
        if (str_starts_with($argumentValue, '@=')) {
            return null;
        }

        $serviceId = str_starts_with($argumentValue, '@') ? substr($argumentValue, 1) : $argumentValue;

        return $this->createConfiguratorFuncCall(self::SERVICE_FUNCTION_NAME, $serviceId);
    }

    private function createConfiguratorFuncCall(string $functionName, string $argumentValue): FuncCall
    {
        return new FuncCall(new Name($functionName), [new Arg(new String_($argumentValue))]);
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

        foreach ($serviceDefinition->getServiceArguments() as $serviceArgument) {
            $methodCall = new MethodCall($methodCall, 'arg', [
                new Arg(new String_($serviceArgument->getName())),
                new Arg($serviceArgument->getValue()),
            ]);
        }

        foreach ($serviceDefinition->getServiceMethodCalls() as $serviceMethodCall) {
            $methodCall = new MethodCall($methodCall, 'call', [
                new Arg(new String_($serviceMethodCall->getMethodName())),
                new Arg($serviceMethodCall->getArguments()),
            ]);
        }

        foreach ($serviceDefinition->getServiceTags() as $serviceTag) {
            $args = [new Arg($serviceTag->getName())];

            if ($serviceTag->hasArguments()) {
                $args[] = new Arg($serviceTag->getArguments());
            }

            $methodCall = new MethodCall($methodCall, 'tag', $args);
        }

        return new Expression($methodCall);
    }

    private function createServiceAliasStmt(string $serviceName, string $className): Expression
    {
        $methodCall = new MethodCall(new Variable(self::SERVICES_VARIABLE_NAME), 'alias', [
            new Arg(new ClassConstFetch(new Name($className), 'class')),
            new Arg(new String_($serviceName)),
        ]);

        return new Expression($methodCall);
    }
}
