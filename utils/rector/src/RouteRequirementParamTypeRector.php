<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Adds int type to untyped controller params, that route requirements restrict to digits.
 */
final class RouteRequirementParamTypeRector extends AbstractRector
{
    private const array DIGIT_REQUIREMENTS = ['\d+', '[0-9]+'];

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        $routeArgs = $this->resolveRouteArgs($node);
        if ([] === $routeArgs || $this->isParentMethod($node)) {
            return null;
        }

        $hasChanged = false;

        foreach ($node->params as $param) {
            if (null !== $param->type || $param->variadic || !is_string($param->var->name)) {
                continue;
            }

            if (!$this->isDigitParam($param->var->name, $routeArgs)) {
                continue;
            }

            if ($param->default instanceof Expr && !$param->default instanceof Int_ && !$this->isNullConst($param->default)) {
                continue;
            }

            $param->type = $param->default instanceof Expr && $this->isNullConst($param->default)
                ? new NullableType(new Identifier('int'))
                : new Identifier('int');

            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * @return array<array<string, Arg>>
     */
    private function resolveRouteArgs(ClassMethod $classMethod): array
    {
        $routeArgs = [];

        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (!$this->isName($attr->name, Route::class)) {
                    continue;
                }

                $namedArgs = [];
                foreach ($attr->args as $arg) {
                    if ($arg->name instanceof Identifier) {
                        $namedArgs[$arg->name->toString()] = $arg;
                    }
                }

                $routeArgs[] = $namedArgs;
            }
        }

        return $routeArgs;
    }

    /**
     * Every route that sets a requirement for the param must restrict it to digits,
     * and a route default for it must be int or null.
     *
     * @param array<array<string, Arg>> $routeArgs
     */
    private function isDigitParam(string $paramName, array $routeArgs): bool
    {
        $hasDigitRequirement = false;

        foreach ($routeArgs as $namedArgs) {
            $requirement = $this->findArrayItemValue($namedArgs['requirements'] ?? null, $paramName);
            if ($requirement instanceof Expr) {
                if (!$requirement instanceof String_ || !in_array($requirement->value, self::DIGIT_REQUIREMENTS, true)) {
                    return false;
                }

                $hasDigitRequirement = true;
            }

            $default = $this->findArrayItemValue($namedArgs['defaults'] ?? null, $paramName);
            if ($default instanceof Expr && !$default instanceof Int_ && !$this->isNullConst($default)) {
                return false;
            }
        }

        return $hasDigitRequirement;
    }

    private function findArrayItemValue(?Arg $arg, string $key): ?Expr
    {
        if (!$arg?->value instanceof Array_) {
            return null;
        }

        foreach ($arg->value->items as $item) {
            if ($item?->key instanceof String_ && $item->key->value === $key) {
                return $item->value;
            }
        }

        return null;
    }

    private function isNullConst(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && $this->isName($expr, 'null');
    }

    /**
     * Typing an overridden param would break compatibility with the parent signature.
     */
    private function isParentMethod(ClassMethod $classMethod): bool
    {
        $classReflection = ScopeFetcher::fetch($classMethod)->getClassReflection();
        if (null === $classReflection) {
            return false;
        }

        $methodName = $this->getName($classMethod);

        foreach ([...$classReflection->getParents(), ...$classReflection->getInterfaces()] as $ancestorReflection) {
            if ($ancestorReflection->hasNativeMethod($methodName)) {
                return true;
            }
        }

        return false;
    }
}
