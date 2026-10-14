<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use Utils\PHPStan\ByReference\ByReferenceParamAnalyzer;

/**
 * Collects every by-reference parameter of every method together with whether the reference is useless
 * in that body. The data is aggregated per override family by NoUselessReferenceParamRule, so an inherited
 * signature is only judged once the whole family is known.
 *
 * @phpstan-type ParamData array{name: string, position: int, line: int, useless: bool}
 *
 * @implements Collector<ClassMethod, array{class: string, method: string, params: list<ParamData>}>
 */
final readonly class ByReferenceParamCollector implements Collector
{
    private ByReferenceParamAnalyzer $byReferenceParamAnalyzer;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->byReferenceParamAnalyzer = new ByReferenceParamAnalyzer($reflectionProvider);
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return array{class: string, method: string, params: list<ParamData>}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return null;
        }

        $byRefParams = [];
        foreach ($node->params as $position => $param) {
            if (!$param->byRef || $param->variadic) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $byRefParams[$position] = $param;
        }

        if ([] === $byRefParams) {
            return null;
        }

        $candidateNames = [];
        foreach ($byRefParams as $param) {
            $candidateNames[] = $param->var->name;
        }

        $uselessByName = $this->byReferenceParamAnalyzer->analyse($candidateNames, $node->getStmts(), $scope);

        $params = [];
        foreach ($byRefParams as $position => $param) {
            $name = $param->var->name;
            $params[] = [
                'name'     => $name,
                'position' => $position,
                'line'     => $param->getStartLine(),
                'useless'  => $uselessByName[$name] ?? false,
            ];
        }

        return [
            'class'  => $classReflection->getName(),
            'method' => $node->name->toString(),
            'params' => $params,
        ];
    }
}
