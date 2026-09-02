<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller action must not declare a parameter it never uses.
 *
 * Symfony resolves each action argument for every request - a route placeholder, the Request, an injected service.
 * A parameter the body never reads costs that resolution for nothing and misleads the reader about what the action
 * needs. Dropping it is safe: actions are dispatched by the argument resolver, not called by name.
 *
 * Only the public "*Action()" methods and "__invoke()" are actions. The shared base controllers - those with
 * "Abstract" or "Common" in their name - are skipped, as a child can override the action with a body that does use
 * the parameter.
 *
 * @implements Rule<ClassMethod>
 */
final class NoUnusedControllerActionParameterRule implements Rule
{
    /**
     * @var string
     */
    private const CONTROLLER_SUFFIX = 'Controller';

    /**
     * @var string
     */
    private const ACTION_SUFFIX = 'action';

    /**
     * @var string
     */
    private const INVOKE_METHOD = '__invoke';

    /**
     * @var string[]
     */
    private const SKIPPED_CLASS_NAME_PARTS = ['Abstract', 'Common'];

    /**
     * These read or write the local scope by variable name, so a parameter can be used without ever appearing as a
     * variable node. When any of them is present the parameters are undecidable and the whole action is left alone.
     *
     * @var string[]
     */
    private const SCOPE_READING_FUNCTIONS = ['compact', 'extract', 'func_get_args', 'get_defined_vars'];

    private NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

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
        if (!$node->isPublic()) {
            return [];
        }

        if (null === $node->stmts) {
            return [];
        }

        if ([] === $node->params) {
            return [];
        }

        if (!$this->isAction($node)) {
            return [];
        }

        if (!$this->isInController($scope)) {
            return [];
        }

        if ($this->readsLocalScopeByName($node->stmts)) {
            return [];
        }

        $usedVariableNames = $this->collectUsedVariableNames($node->stmts);

        $ruleErrors = [];

        foreach ($node->params as $param) {
            // a by-reference parameter is written for its caller, not read here
            if ($param->byRef) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $parameterName = $param->var->name;
            if (isset($usedVariableNames[$parameterName])) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Controller action "%s()" declares parameter "$%s" that is never used. Remove it.',
                $node->name->toString(),
                $parameterName
            ))
                ->identifier('mautic.noUnusedControllerActionParameter')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isAction(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toLowerString();

        return str_ends_with($methodName, self::ACTION_SUFFIX) || self::INVOKE_METHOD === $methodName;
    }

    private function isInController(Scope $scope): bool
    {
        // inside a trait the scope class is the controller using it, so the trait method would be checked repeatedly
        if ($scope->isInTrait()) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        $className = $classReflection->getName();
        if (!str_ends_with($className, self::CONTROLLER_SUFFIX)) {
            return false;
        }

        return !$this->isSharedBaseController($className);
    }

    private function isSharedBaseController(string $className): bool
    {
        $lastSeparatorPosition = strrpos($className, '\\');
        $shortClassName        = false === $lastSeparatorPosition ? $className : substr($className, $lastSeparatorPosition + 1);

        foreach (self::SKIPPED_CLASS_NAME_PARTS as $skippedClassNamePart) {
            if (str_contains($shortClassName, $skippedClassNamePart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function readsLocalScopeByName(array $stmts): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($stmts, FuncCall::class) as $funcCall) {
            if (!$funcCall->name instanceof Name) {
                continue;
            }

            if (in_array($funcCall->name->toLowerString(), self::SCOPE_READING_FUNCTIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node\Stmt[] $stmts
     *
     * @return array<string, true>
     */
    private function collectUsedVariableNames(array $stmts): array
    {
        $usedVariableNames = [];

        foreach ($this->nodeFinder->findInstanceOf($stmts, Variable::class) as $variable) {
            if (is_string($variable->name)) {
                $usedVariableNames[$variable->name] = true;
            }
        }

        return $usedVariableNames;
    }
}
