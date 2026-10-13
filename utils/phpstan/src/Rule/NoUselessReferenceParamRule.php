<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A by-reference parameter that is never written to in the method body does nothing: the caller can
 * never observe a change. Object/array mutation does not need a reference. Drop the "&".
 *
 * Conservative: if the variable escapes (passed to a call whose parameter may be by-reference, aliased
 * by &, captured by-reference in a closure, or reachable via compact()/extract()), the parameter is
 * left alone.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoUselessReferenceParamRuleTest
 */
final readonly class NoUselessReferenceParamRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
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
        $stmts = $node->getStmts();
        if (null === $stmts) {
            return [];
        }

        // PHP forces an overriding method to keep the parent's by-reference-ness, so a by-reference
        // parameter may be mandated by an inherited signature rather than used. Only flag methods that
        // cannot take part in such a mismatch: not overriding a parent, and not overridable themselves.
        if ($this->overridesParentMethod($node, $scope) || $this->isOverridable($node, $scope)) {
            return [];
        }

        $candidateNames = [];
        foreach ($node->params as $param) {
            if (!$param->byRef || $param->variadic) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $candidateNames[] = $param->var->name;
        }

        if ([] === $candidateNames) {
            return [];
        }

        $visitor = new class($candidateNames, $this->reflectionProvider, $scope) extends NodeVisitorAbstract {
            public bool $bailout = false;

            /**
             * @var array<string, true>
             */
            public array $written = [];

            /**
             * @var array<string, true>
             */
            public array $escaped = [];

            /**
             * @param list<string> $names
             */
            public function __construct(
                private readonly array $names,
                private readonly ReflectionProvider $reflectionProvider,
                private readonly Scope $scope,
            ) {
            }

            public function enterNode(Node $node): ?int
            {
                // nested scopes have their own symbol table; only their by-reference captures reach us
                if ($node instanceof Closure) {
                    foreach ($node->uses as $use) {
                        if ($use->byRef && $use->var instanceof Variable && is_string($use->var->name)) {
                            $this->markEscaped($use->var->name);
                        }
                    }

                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof ArrowFunction || $node instanceof Function_) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof ArrayItem && $node->byRef) {
                    // a reference inside an array literal, e.g. [&$x], aliases the variable out
                    $this->escapeIfCandidate($node->value);
                }

                if ($node instanceof Variable && !is_string($node->name)) {
                    // variable-variable may touch anything in this scope
                    $this->bailout = true;

                    return null;
                }

                if ($node instanceof Global_) {
                    foreach ($node->vars as $var) {
                        if ($var instanceof Variable && is_string($var->name)) {
                            $this->markEscaped($var->name);
                        }
                    }

                    return null;
                }

                if ($node instanceof Static_) {
                    foreach ($node->vars as $staticVar) {
                        if ($staticVar->var instanceof Variable && is_string($staticVar->var->name)) {
                            $this->markEscaped($staticVar->var->name);
                        }
                    }

                    return null;
                }

                if ($node instanceof Assign || $node instanceof AssignOp) {
                    $this->markWrittenTarget($node->var);
                }

                if ($node instanceof AssignRef) {
                    $this->markWrittenTarget($node->var);
                    // right-hand side is aliased out and may be changed through the new name
                    if ($node->expr instanceof Variable && is_string($node->expr->name)) {
                        $this->markEscaped($node->expr->name);
                    }
                }

                if ($node instanceof PreInc || $node instanceof PreDec || $node instanceof PostInc || $node instanceof PostDec) {
                    $this->markWrittenTarget($node->var);
                }

                if ($node instanceof Foreach_) {
                    if (null !== $node->keyVar) {
                        $this->markWrittenTarget($node->keyVar);
                    }

                    $this->markWrittenTarget($node->valueVar);
                }

                if ($node instanceof FuncCall) {
                    $this->handleFuncCall($node);
                }

                if ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall || $node instanceof New_) {
                    // callee signature needs the inner scope we do not have here, so stay safe
                    $this->escapeArgs($node->args);
                }

                return null;
            }

            private function handleFuncCall(FuncCall $funcCall): void
            {
                if (!$funcCall->name instanceof Name) {
                    // dynamic callee, cannot resolve
                    $this->escapeArgs($funcCall->args);

                    return;
                }

                $name = $funcCall->name;
                if (in_array($name->toLowerString(), ['compact', 'extract', 'get_defined_vars', 'func_get_args', 'func_get_arg'], true)) {
                    $this->bailout = true;

                    return;
                }

                $args = $funcCall->args;
                if (!$this->reflectionProvider->hasFunction($name, $this->scope)) {
                    $this->escapeArgs($funcCall->args);

                    return;
                }

                $parametersAcceptor = $this->reflectionProvider->getFunction($name, $this->scope)->getVariants();
                foreach ($args as $position => $arg) {
                    if (!$arg instanceof Arg) {
                        continue;
                    }

                    if (null !== $arg->name || $arg->unpack) {
                        // named args / spread blur the position-to-parameter mapping
                        $this->escapeIfCandidate($arg->value);

                        continue;
                    }

                    if (!$this->isDefinitelyNotByRef($parametersAcceptor, $position)) {
                        $this->escapeIfCandidate($arg->value);
                    }
                }
            }

            /**
             * @param list<\PHPStan\Reflection\ParametersAcceptor> $variants
             */
            private function isDefinitelyNotByRef(array $variants, int $position): bool
            {
                foreach ($variants as $variant) {
                    $parameters = $variant->getParameters();
                    $parameter = $parameters[$position] ?? null;
                    if (null === $parameter) {
                        $last = $parameters[count($parameters) - 1] ?? null;
                        if (null !== $last && $last->isVariadic()) {
                            $parameter = $last;
                        }
                    }

                    if (null === $parameter) {
                        return false;
                    }

                    if (!$parameter->passedByReference()->no()) {
                        return false;
                    }
                }

                return [] !== $variants;
            }

            /**
             * @param array<Arg|\PhpParser\Node\VariadicPlaceholder> $args
             */
            private function escapeArgs(array $args): void
            {
                foreach ($args as $arg) {
                    if ($arg instanceof Arg) {
                        $this->escapeIfCandidate($arg->value);
                    }
                }
            }

            private function escapeIfCandidate(Expr $expr): void
            {
                if ($expr instanceof Variable && is_string($expr->name)) {
                    $this->markEscaped($expr->name);
                }
            }

            private function markWrittenTarget(?Expr $expr): void
            {
                if (null === $expr) {
                    return;
                }

                $name = $this->rootVariableName($expr);
                if (null !== $name) {
                    $this->markWritten($name);

                    return;
                }

                if ($expr instanceof Node\Expr\Array_ || $expr instanceof Node\Expr\List_) {
                    foreach ($expr->items as $item) {
                        if (null !== $item) {
                            $this->markWrittenTarget($item->value);
                        }
                    }
                }
            }

            private function rootVariableName(Expr $expr): ?string
            {
                while ($expr instanceof ArrayDimFetch) {
                    $expr = $expr->var;
                }

                if ($expr instanceof Variable && is_string($expr->name)) {
                    return $expr->name;
                }

                return null;
            }

            private function markWritten(string $name): void
            {
                if (in_array($name, $this->names, true)) {
                    $this->written[$name] = true;
                }
            }

            private function markEscaped(string $name): void
            {
                if (in_array($name, $this->names, true)) {
                    $this->escaped[$name] = true;
                }
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        if ($visitor->bailout) {
            return [];
        }

        $ruleErrors = [];
        foreach ($node->params as $param) {
            if (!$param->byRef || $param->variadic) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $paramName = $param->var->name;
            if (isset($visitor->written[$paramName]) || isset($visitor->escaped[$paramName])) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Parameter "$%s" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                $paramName
            ))
                ->identifier('mautic.noUselessReferenceParam')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isOverridable(ClassMethod $classMethod, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return false;
        }

        // nothing extends a final class, so its methods cannot be overridden
        if ($classReflection->isFinal()) {
            return false;
        }

        $methodName = $classMethod->name->toString();
        if (!$classReflection->hasNativeMethod($methodName)) {
            return true;
        }

        // a private or final method cannot be overridden either
        $methodReflection = $classReflection->getNativeMethod($methodName);

        return !$methodReflection->isPrivate() && !$methodReflection->isFinal()->yes();
    }

    private function overridesParentMethod(ClassMethod $classMethod, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return false;
        }

        $methodName = $classMethod->name->toString();

        foreach ($classReflection->getParents() as $parent) {
            if ($parent->hasNativeMethod($methodName)) {
                return true;
            }
        }

        foreach ($classReflection->getInterfaces() as $interface) {
            if ($interface->hasNativeMethod($methodName)) {
                return true;
            }
        }

        return false;
    }
}
