<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A service injected in __construct() or an autowire*() method must not be handed over to another method call.
 *
 * Passing an own dependency around is service juggling - the service travels through a parameter list instead of
 * being injected where it is used:
 *
 *     public function getContactEventsAction(Request $request): Response
 *     {
 *         return $this->getEntitiesAction($request, $this->userHelper);
 *     }
 *
 * The called method has a constructor of its own, so it can take the service directly:
 *
 *     public function __construct(
 *         private readonly UserHelper $userHelper,
 *     ) {
 *     }
 *
 *     public function getContactEventsAction(Request $request): Response
 *     {
 *         return $this->getEntitiesAction($request);
 *     }
 *
 * @implements Rule<InClassNode>
 */
final class NoServiceJugglingRule implements Rule
{
    /**
     * @var string
     */
    private const CONSTRUCTOR_NAME = '__construct';

    /**
     * @var string
     */
    private const AUTOWIRE_PREFIX = 'autowire';

    /**
     * @var string
     */
    private const REQUIRED_ATTRIBUTE = 'Symfony\Contracts\Service\Attribute\Required';

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
        $class = $node->getOriginalNode();
        if (!$class instanceof Class_) {
            return [];
        }

        $classReflection = $node->getClassReflection();

        $injectedServiceTypes = $this->resolveInjectedServiceTypes($class);
        if ([] === $injectedServiceTypes) {
            return [];
        }

        $ownMethodCalls = $this->resolveOwnMethodCalls($class, $classReflection);
        if ([] === $ownMethodCalls) {
            return [];
        }

        $constantArgumentKeys = $this->resolveConstantArgumentKeys($ownMethodCalls);

        $ruleErrors = [];

        foreach ($ownMethodCalls as [$call, $calledMethodName]) {
            foreach ($call->getArgs() as $position => $arg) {
                $propertyName = $this->matchThisPropertyName($arg->value);
                if (null === $propertyName || !isset($injectedServiceTypes[$propertyName])) {
                    continue;
                }

                if (!isset($constantArgumentKeys[$this->createArgumentKey($call, $calledMethodName, $arg, $position)])) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service "$this->%s" is passed to "%s()" as an argument. Inject "%s" in the constructor of the class that uses it instead.',
                    $propertyName,
                    $calledMethodName,
                    $injectedServiceTypes[$propertyName]
                ))
                    ->identifier('mautic.noServiceJuggling')
                    ->line($arg->getStartLine())
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * Properties filled by __construct() or an autowire*() method, e.g. "userHelper" => "Mautic\...\UserHelper".
     *
     * @return array<string, string>
     */
    private function resolveInjectedServiceTypes(Class_ $class): array
    {
        $injectedServiceTypes = [];

        foreach ($class->getMethods() as $classMethod) {
            if (!$this->isInjectingMethod($classMethod)) {
                continue;
            }

            $paramTypes = [];

            foreach ($classMethod->params as $param) {
                $className = $this->matchObjectTypeName($param);
                if (null === $className) {
                    continue;
                }

                if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                    continue;
                }

                $paramTypes[$param->var->name] = $className;

                // promoted property keeps the param name
                if (0 !== $param->flags) {
                    $injectedServiceTypes[$param->var->name] = $className;
                }
            }

            foreach ($this->resolveAssignedProperties($classMethod) as $propertyName => $variableName) {
                if (isset($paramTypes[$variableName])) {
                    $injectedServiceTypes[$propertyName] = $paramTypes[$variableName];
                }
            }
        }

        return $injectedServiceTypes;
    }

    private function isInjectingMethod(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toString();

        if (self::CONSTRUCTOR_NAME === $classMethod->name->toLowerString()) {
            return true;
        }

        if (str_starts_with($methodName, self::AUTOWIRE_PREFIX)) {
            return true;
        }

        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::REQUIRED_ATTRIBUTE === $attr->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The "$this->userHelper = $userHelper;" assignments, as "userHelper" => "userHelper".
     *
     * @return array<string, string>
     */
    private function resolveAssignedProperties(ClassMethod $classMethod): array
    {
        $assignedProperties = [];

        $nodeFinder = new NodeFinder();

        /** @var Node\Expr\Assign[] $assigns */
        $assigns = $nodeFinder->findInstanceOf((array) $classMethod->stmts, Node\Expr\Assign::class);

        foreach ($assigns as $assign) {
            $propertyName = $this->matchThisPropertyName($assign->var);
            if (null === $propertyName) {
                continue;
            }

            if (!$assign->expr instanceof Variable || !is_string($assign->expr->name)) {
                continue;
            }

            $assignedProperties[$propertyName] = $assign->expr->name;
        }

        return $assignedProperties;
    }

    /**
     * The calls that stay inside the class hierarchy: "$this->someMethod()" and "parent::someMethod()".
     *
     * @return list<MethodCall|StaticCall>
     */
    private function resolveLocalCalls(ClassMethod $classMethod): array
    {
        $nodeFinder = new NodeFinder();

        /** @var array<MethodCall|StaticCall> $calls */
        $calls = $nodeFinder->find((array) $classMethod->stmts, static function (Node $node): bool {
            if ($node instanceof MethodCall) {
                return $node->var instanceof Variable && 'this' === $node->var->name;
            }

            if ($node instanceof StaticCall) {
                return $node->class instanceof Name && 'parent' === $node->class->toLowerString();
            }

            return false;
        });

        return array_values($calls);
    }

    /**
     * The calls to a method the class itself owns, so the dependency can be moved into a constructor.
     *
     * @return list<array{MethodCall|StaticCall, string}>
     */
    private function resolveOwnMethodCalls(Class_ $class, ClassReflection $classReflection): array
    {
        $ownMethodCalls = [];

        foreach ($class->getMethods() as $classMethod) {
            foreach ($this->resolveLocalCalls($classMethod) as $call) {
                // "$this->toText(...)" has no arguments to look at
                if ($call->isFirstClassCallable()) {
                    continue;
                }

                $calledMethodName = $call->name instanceof Identifier ? $call->name->toString() : null;
                if (null === $calledMethodName) {
                    continue;
                }

                if (!$this->isOwnMethod($call, $class, $classReflection, $calledMethodName)) {
                    continue;
                }

                $ownMethodCalls[] = [$call, $calledMethodName];
            }
        }

        return $ownMethodCalls;
    }

    /**
     * A method the class can change: one declared right here, or the one of the parent an explicit "parent::" targets.
     *
     * An inherited method reached through "$this->" is left out: it is shared by every child, so the service is an
     * argument of the call, not a dependency of the method - e.g. FormModel::createForm() takes the form factory of
     * whoever builds the form. The same goes for a method brought in by a trait, as a trait has no constructor.
     *
     * @param MethodCall|StaticCall $call
     */
    private function isOwnMethod(Node $call, Class_ $class, ClassReflection $classReflection, string $methodName): bool
    {
        if ($call instanceof StaticCall) {
            return !$this->isDeclaredInTrait($classReflection->getParentClass(), $methodName);
        }

        return $class->getMethod($methodName) instanceof ClassMethod;
    }

    /**
     * The argument positions that always get the very same value, as only those stand for a fixed dependency.
     *
     * A position filled differently by another call is a parameter of its own: LeadStageLogRepository runs the same
     * query on the injected unbuffered connection and on the active one, EventLogApiController fetches one batch
     * with the event model and the next one with the contact model.
     *
     * @param list<array{MethodCall|StaticCall, string}> $ownMethodCalls
     *
     * @return array<string, true>
     */
    private function resolveConstantArgumentKeys(array $ownMethodCalls): array
    {
        $argumentValues = [];

        foreach ($ownMethodCalls as [$call, $calledMethodName]) {
            foreach ($call->getArgs() as $position => $arg) {
                $argumentKey = $this->createArgumentKey($call, $calledMethodName, $arg, $position);

                $argumentValues[$argumentKey][$this->matchThisPropertyName($arg->value) ?? '#other'] = true;
            }
        }

        $constantArgumentKeys = [];

        foreach ($argumentValues as $argumentKey => $values) {
            if (1 === count($values)) {
                $constantArgumentKeys[$argumentKey] = true;
            }
        }

        return $constantArgumentKeys;
    }

    /**
     * @param MethodCall|StaticCall $call
     */
    private function createArgumentKey(Node $call, string $calledMethodName, Arg $arg, int $position): string
    {
        $callKind = $call instanceof StaticCall ? 'parent' : 'this';
        $argName  = $arg->name instanceof Identifier ? $arg->name->toString() : (string) $position;

        return $callKind.'::'.$calledMethodName.'#'.$argName;
    }

    /**
     * A trait has no constructor to inject into, so its methods can only take the service as a parameter.
     */
    private function isDeclaredInTrait(?ClassReflection $classReflection, string $methodName): bool
    {
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        foreach ($classReflection->getTraits(true) as $traitReflection) {
            if ($traitReflection->hasNativeMethod($methodName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The property name of "$this->userHelper", null for anything else.
     */
    private function matchThisPropertyName(Node $node): ?string
    {
        if (!$node instanceof PropertyFetch) {
            return null;
        }

        if (!$node->var instanceof Variable || 'this' !== $node->var->name) {
            return null;
        }

        if (!$node->name instanceof Identifier) {
            return null;
        }

        return $node->name->toString();
    }

    /**
     * Only class types count, a scalar or an array is a value, not a service.
     */
    private function matchObjectTypeName(Param $param): ?string
    {
        $type = $param->type;
        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        if (!$type instanceof Name) {
            return null;
        }

        return $type->toString();
    }
}
