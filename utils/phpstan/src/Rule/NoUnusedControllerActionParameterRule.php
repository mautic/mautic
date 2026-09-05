<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller action must not declare a parameter it never uses.
 *
 * Symfony resolves each action argument for every request - a route placeholder, the Request, an injected service.
 * A parameter the body never reads costs that resolution for nothing and misleads the reader about what the action
 * needs. Dropping it is safe: actions are dispatched by the argument resolver, which binds by name.
 *
 * Only the public "*Action()" methods and "__invoke()" are actions. The shared base controllers - those with
 * "Abstract" or "Common" in their name - are skipped, as a child can override the action with a body that does use
 * the parameter.
 *
 * A controller that dispatches its own actions by a dynamic call - "$this->{$name.'Action'}($request, $id, ...)" -
 * is skipped whole: that call hands a fixed, positional argument list to every action it reaches, so a parameter
 * unused in one action body is still required to keep the positions aligned.
 *
 * @implements Rule<InClassNode>
 */
final readonly class NoUnusedControllerActionParameterRule implements Rule
{
    private const string CONTROLLER_SUFFIX = 'Controller';

    private const string ACTION_SUFFIX = 'action';

    private const string INVOKE_METHOD = '__invoke';

    private const string THIS = 'this';

    /**
     * @var string[]
     */
    private const array SKIPPED_CLASS_NAME_PARTS = ['Abstract', 'Common'];

    /**
     * These read or write the local scope by variable name, so a parameter can be used without ever appearing as a
     * variable node. When any of them is present the parameters are undecidable and the whole action is left alone.
     *
     * @var string[]
     */
    private const array SCOPE_READING_FUNCTIONS = ['compact', 'extract', 'func_get_args', 'get_defined_vars'];

    private NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $className = $node->getClassReflection()->getName();
        if (!str_ends_with($className, self::CONTROLLER_SUFFIX)) {
            return [];
        }

        if ($this->isSharedBaseController($className)) {
            return [];
        }

        // a trait, interface or enum is not a controller with its own dispatched actions
        $classNode = $node->getOriginalNode();
        if (!$classNode instanceof Class_) {
            return [];
        }

        if ($this->dispatchesActionsPositionally($classNode)) {
            return [];
        }

        $ruleErrors = [];

        foreach ($classNode->getMethods() as $classMethod) {
            foreach ($this->processMethod($classMethod) as $ruleError) {
                $ruleErrors[] = $ruleError;
            }
        }

        return $ruleErrors;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processMethod(ClassMethod $classMethod): array
    {
        if (!$classMethod->isPublic()) {
            return [];
        }

        if (null === $classMethod->stmts) {
            return [];
        }

        if ([] === $classMethod->params) {
            return [];
        }

        if (!$this->isAction($classMethod)) {
            return [];
        }

        if ($this->readsLocalScopeByName($classMethod->stmts)) {
            return [];
        }

        $usedVariableNames = $this->collectUsedVariableNames($classMethod->stmts);

        $ruleErrors = [];

        foreach ($classMethod->params as $param) {
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
                $classMethod->name->toString(),
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

    private function isSharedBaseController(string $className): bool
    {
        $lastSeparatorPosition = strrpos($className, '\\');
        $shortClassName        = false === $lastSeparatorPosition ? $className : substr($className, $lastSeparatorPosition + 1);

        return array_any(self::SKIPPED_CLASS_NAME_PARTS, fn (string $skippedClassNamePart): bool => str_contains($shortClassName, $skippedClassNamePart));
    }

    /**
     * A "$this->{$expr}(...)" call names its method dynamically, so it reaches sibling actions with a fixed argument
     * list that PHPStan cannot see here.
     */
    private function dispatchesActionsPositionally(Class_ $class): bool
    {
        foreach ($this->nodeFinder->findInstanceOf($class, MethodCall::class) as $methodCall) {
            if ($methodCall->name instanceof Identifier) {
                continue;
            }

            if ($methodCall->var instanceof Variable && self::THIS === $methodCall->var->name) {
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
