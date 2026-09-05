<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Reports the services of Config/services.php registered one by one although the load() call above
 * already picked them up, e.g.:
 *
 *     $services->load('Mautic\\CampaignBundle\\', '../')
 *         ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
 *
 *     $services->set(Mautic\CampaignBundle\Membership\MembershipManager::class);
 *
 * The set() call repeats what the load() call says, the class is autowired either way. Only the plain
 * set() call is reported, a set() call that configures the service says more than the load() call does:
 *
 *     $services->set(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)->tag('kernel.reset');
 *
 * A class the exclude() call leaves out of the load() is kept, and so is a class of another namespace or
 * of another directory than the load() call walks.
 *
 * @implements Rule<FileNode>
 */
final readonly class NoAlreadyLoadedServiceSetRule implements Rule
{
    private const string SERVICES_FILE_NAME = 'services.php';

    /**
     * The variable the services of a Config/services.php file are registered on.
     */
    private const string SERVICES_VARIABLE_NAME = 'services';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param FileNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        $nodeFinder = new NodeFinder();
        $stmts      = $node->getNodes();

        $loads = $this->resolveLoads($stmts, $nodeFinder, dirname($scope->getFile()));
        if ([] === $loads) {
            return [];
        }

        $ruleErrors = [];

        /** @var Expression[] $expressions */
        $expressions = $nodeFinder->findInstanceOf($stmts, Expression::class);

        foreach ($expressions as $expression) {
            // a chained set() call, e.g. ->tag() or ->decorate(), configures more than the load() call does
            if (!$expression->expr instanceof MethodCall) {
                continue;
            }

            $className = $this->matchSetClassName($expression->expr);
            if (null === $className) {
                continue;
            }

            $classFilePath = $this->resolveAutowiredClassFilePath($className);
            if (null === $classFilePath) {
                continue;
            }

            foreach ($loads as [$namespacePrefix, $directoryPath, $excludedPaths]) {
                if (!str_starts_with($className, $namespacePrefix)) {
                    continue;
                }

                if (!str_starts_with($classFilePath, $directoryPath.'/')) {
                    continue;
                }

                if ($this->isExcludedPath($classFilePath, $excludedPaths)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service "%s" is already registered by the $services->load("%s") call above, remove the $services->set() call.',
                    $className,
                    $namespacePrefix
                ))
                    ->identifier('mautic.noAlreadyLoadedServiceSet')
                    ->line($expression->expr->getStartLine())
                    ->build();

                break;
            }
        }

        return $ruleErrors;
    }

    /**
     * @param Node[] $stmts
     *
     * @return list<array{string, string, list<string>}> the namespace prefix, the absolute directory
     *                                                   and the absolute excluded paths of every load() call
     */
    private function resolveLoads(array $stmts, NodeFinder $nodeFinder, string $configDirectoryPath): array
    {
        $loads = [];

        /** @var MethodCall[] $methodCalls */
        $methodCalls = $nodeFinder->findInstanceOf($stmts, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (!$this->isServicesCall($methodCall, 'load')) {
                continue;
            }

            $args = $methodCall->getArgs();
            if (2 !== count($args)) {
                continue;
            }

            if (!$args[0]->value instanceof String_ || !$args[1]->value instanceof String_) {
                continue;
            }

            // a resource of single files, e.g. "../Entity/*Repository.php", registers no whole directory
            if (str_contains($args[1]->value->value, '*')) {
                continue;
            }

            $directoryPath = $this->normalizePath($configDirectoryPath.'/'.$args[1]->value->value);

            $loads[] = [
                $args[0]->value->value,
                $directoryPath,
                $this->resolveExcludedPaths($methodCall, $methodCalls, $stmts, $nodeFinder, $directoryPath),
            ];
        }

        return $loads;
    }

    /**
     * @param MethodCall[] $methodCalls
     * @param Node[]       $stmts
     *
     * @return list<string> the absolute paths the exclude() call of the load() keeps out of the container
     */
    private function resolveExcludedPaths(
        MethodCall $loadMethodCall,
        array $methodCalls,
        array $stmts,
        NodeFinder $nodeFinder,
        string $directoryPath,
    ): array {
        $excludedPaths = [];

        foreach ($methodCalls as $methodCall) {
            if ($methodCall->var !== $loadMethodCall) {
                continue;
            }

            if (!$methodCall->name instanceof Identifier || 'exclude' !== $methodCall->name->toString()) {
                continue;
            }

            foreach ($methodCall->getArgs() as $arg) {
                foreach ($this->resolveStringValues($arg->value, $stmts, $nodeFinder) as $stringValue) {
                    $excludedPaths[] = $this->normalizePath($directoryPath.'/'.$stringValue);
                }
            }
        }

        return $excludedPaths;
    }

    /**
     * The exclude() argument is built at runtime, e.g. '../{'.implode(',', array_merge(A::EXCLUDES, $excludes)).'}'.
     * Every string it is made of is taken as an excluded path - the glue strings, e.g. "../{" or ",", match no
     * class file, while a missed path would make a kept set() call look like a duplicate.
     *
     * @param Node[] $stmts
     *
     * @return list<string>
     */
    private function resolveStringValues(Expr $expr, array $stmts, NodeFinder $nodeFinder): array
    {
        $stringValues = [];

        /** @var String_[] $strings */
        $strings = $nodeFinder->findInstanceOf([$expr], String_::class);
        foreach ($strings as $string) {
            $stringValues[] = $string->value;
        }

        /** @var ClassConstFetch[] $classConstFetches */
        $classConstFetches = $nodeFinder->findInstanceOf([$expr], ClassConstFetch::class);
        foreach ($classConstFetches as $classConstFetch) {
            foreach ($this->resolveClassConstantStringValues($classConstFetch, $nodeFinder) as $stringValue) {
                $stringValues[] = $stringValue;
            }
        }

        /** @var Variable[] $variables */
        $variables = $nodeFinder->findInstanceOf([$expr], Variable::class);
        foreach ($variables as $variable) {
            foreach ($this->resolveVariableStringValues($variable, $stmts, $nodeFinder) as $stringValue) {
                $stringValues[] = $stringValue;
            }
        }

        return $stringValues;
    }

    /**
     * @return list<string>
     */
    private function resolveClassConstantStringValues(ClassConstFetch $classConstFetch, NodeFinder $nodeFinder): array
    {
        if (!$classConstFetch->class instanceof Name || !$classConstFetch->name instanceof Identifier) {
            return [];
        }

        $className    = $classConstFetch->class->toString();
        $constantName = $classConstFetch->name->toString();

        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasConstant($constantName)) {
            return [];
        }

        $stringValues = [];

        /** @var String_[] $strings */
        $strings = $nodeFinder->findInstanceOf([$classReflection->getConstant($constantName)->getValueExpr()], String_::class);
        foreach ($strings as $string) {
            $stringValues[] = $string->value;
        }

        return $stringValues;
    }

    /**
     * @param Node[] $stmts
     *
     * @return list<string>
     */
    private function resolveVariableStringValues(Variable $variable, array $stmts, NodeFinder $nodeFinder): array
    {
        if (!is_string($variable->name)) {
            return [];
        }

        $stringValues = [];

        /** @var Expr\Assign[] $assigns */
        $assigns = $nodeFinder->findInstanceOf($stmts, Expr\Assign::class);
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof Variable || $assign->var->name !== $variable->name) {
                continue;
            }

            /** @var String_[] $strings */
            $strings = $nodeFinder->findInstanceOf([$assign->expr], String_::class);
            foreach ($strings as $string) {
                $stringValues[] = $string->value;
            }
        }

        return $stringValues;
    }

    /**
     * @param list<string> $excludedPaths
     */
    private function isExcludedPath(string $classFilePath, array $excludedPaths): bool
    {
        foreach ($excludedPaths as $excludedPath) {
            if ($classFilePath === $excludedPath || str_starts_with($classFilePath, $excludedPath.'/')) {
                return true;
            }

            if (fnmatch($excludedPath, $classFilePath) || fnmatch($excludedPath.'/*', $classFilePath)) {
                return true;
            }
        }

        return false;
    }

    private function matchSetClassName(MethodCall $methodCall): ?string
    {
        if (!$this->isServicesCall($methodCall, 'set')) {
            return null;
        }

        $args = $methodCall->getArgs();
        if (1 !== count($args)) {
            return null;
        }

        $classValue = $args[0]->value;
        if (!$classValue instanceof ClassConstFetch || !$classValue->class instanceof Name) {
            return null;
        }

        if (!$classValue->name instanceof Identifier || 'class' !== $classValue->name->toLowerString()) {
            return null;
        }

        return $classValue->class->toString();
    }

    private function isServicesCall(MethodCall $methodCall, string $methodName): bool
    {
        if (!$methodCall->name instanceof Identifier || $methodName !== $methodCall->name->toString()) {
            return false;
        }

        return $methodCall->var instanceof Variable && self::SERVICES_VARIABLE_NAME === $methodCall->var->name;
    }

    /**
     * @return string|null the file of a class the load() call would autowire, null for anything else
     */
    private function resolveAutowiredClassFilePath(string $className): ?string
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if ($classReflection->isAbstract() || $classReflection->isInterface() || $classReflection->isTrait()) {
            return null;
        }

        $fileName = $classReflection->getFileName();
        if (null === $fileName) {
            return null;
        }

        return $this->normalizePath($fileName);
    }

    /**
     * Resolves the "." and ".." parts of a path without touching the disk - an excluded path holds a glob,
     * so realpath() would give nothing back for it.
     */
    private function normalizePath(string $path): string
    {
        $normalizedParts = [];

        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }

            if ('..' === $part && [] !== $normalizedParts && '..' !== end($normalizedParts)) {
                array_pop($normalizedParts);
                continue;
            }

            $normalizedParts[] = $part;
        }

        return (str_starts_with($path, '/') ? '/' : '').implode('/', $normalizedParts);
    }
}
