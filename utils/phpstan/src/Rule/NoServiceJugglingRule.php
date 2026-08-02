<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
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
 * @implements Rule<Class_>
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
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $injectedServiceTypes = $this->resolveInjectedServiceTypes($node);
        if ([] === $injectedServiceTypes) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->getMethods() as $classMethod) {
            foreach ($this->resolveLocalCalls($classMethod) as $call) {
                // "$this->toText(...)" has no arguments to look at
                if ($call->isFirstClassCallable()) {
                    continue;
                }

                $calledMethodName = $call->name instanceof Identifier ? $call->name->toString() : null;
                if (null === $calledMethodName) {
                    continue;
                }

                foreach ($call->getArgs() as $arg) {
                    $propertyName = $this->matchThisPropertyName($arg->value);
                    if (null === $propertyName || !isset($injectedServiceTypes[$propertyName])) {
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
