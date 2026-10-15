<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\FileTypeMapper;

/**
 * Collects, for every method, the untyped parameters whose only type is an object-type param docblock and that
 * have a method called on them in the body. The override family is judged later by
 * MoveObjectParamDocblockToNativeTypeRule, so a parameter is only narrowed when no method in the family stands
 * in the way.
 *
 * @phpstan-type CandidateParam array{name: string, type: string, line: int}
 *
 * @implements Collector<ClassMethod, array{class: string, method: string, candidates: list<CandidateParam>}>
 */
final readonly class ObjectParamDocblockCollector implements Collector
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper,
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return array{class: string, method: string, candidates: list<CandidateParam>}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return null;
        }

        $methodName = $node->name->toString();

        // every declaration is emitted so the rule can see the whole override family, not just the candidates
        $entry = [
            'class'      => $classReflection->getName(),
            'method'     => $methodName,
            'candidates' => [],
        ];

        if (null === $node->stmts) {
            return $entry;
        }

        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return $entry;
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->isInTrait() ? $classReflection->getName() : null,
            $methodName,
            $docComment->getText()
        );

        $paramTags = $resolvedPhpDoc->getParamTags();

        foreach ($node->params as $param) {
            if (null !== $param->type) {
                continue;
            }

            // a default value means the parameter is optional or nullable, so leave the signature alone
            if (null !== $param->default) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $parameterName = $param->var->name;
            if (!isset($paramTags[$parameterName])) {
                continue;
            }

            $paramType = $paramTags[$parameterName]->getType();

            // a single concrete class only, so "object", nullable "?Foo" and unions "Foo|Bar" are left alone
            if (!$paramType->isObject()->yes()) {
                continue;
            }

            $objectClassNames = $paramType->getObjectClassNames();
            if (1 !== count($objectClassNames)) {
                continue;
            }

            if (!$this->hasMethodCallOnVariable($node->stmts, $parameterName)) {
                continue;
            }

            // the docblock type is not trusted when the body itself checks the variable against it
            if ($this->hasInstanceofOnVariable($node->stmts, $parameterName)) {
                continue;
            }

            $entry['candidates'][] = [
                'name' => $parameterName,
                'type' => $objectClassNames[0],
                'line' => $param->getStartLine(),
            ];
        }

        return $entry;
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function hasMethodCallOnVariable(array $stmts, string $parameterName): bool
    {
        $methodCalls = (new NodeFinder())->find($stmts, static function (Node $subNode) use ($parameterName): bool {
            if (!$subNode instanceof MethodCall && !$subNode instanceof NullsafeMethodCall) {
                return false;
            }

            return $subNode->var instanceof Variable && $subNode->var->name === $parameterName;
        });

        return [] !== $methodCalls;
    }

    /**
     * @param Node\Stmt[] $stmts
     */
    private function hasInstanceofOnVariable(array $stmts, string $parameterName): bool
    {
        $instanceofs = (new NodeFinder())->find($stmts, static function (Node $subNode) use ($parameterName): bool {
            if (!$subNode instanceof Instanceof_) {
                return false;
            }

            return $subNode->expr instanceof Variable && $subNode->expr->name === $parameterName;
        });

        return [] !== $instanceofs;
    }
}
