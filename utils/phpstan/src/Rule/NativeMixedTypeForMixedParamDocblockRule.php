<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\MixedType;

/**
 * A param documented as "mixed" with no native type must declare the native "mixed" type.
 * Applies to methods and plain functions. Safe in overrides too: a native "mixed" param is as wide as an untyped one.
 *
 * @implements Rule<Node\FunctionLike>
 *
 * @see \Utils\PHPStan\Tests\Rule\NativeMixedTypeForMixedParamDocblockRuleTest
 */
final readonly class NativeMixedTypeForMixedParamDocblockRule implements Rule
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper
    ) {
    }

    public function getNodeType(): string
    {
        return Node\FunctionLike::class;
    }

    /**
     * @param Node\FunctionLike $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // a closure docblock attaches to the enclosing statement, not to the closure node
        if (!$node instanceof ClassMethod && !$node instanceof Function_) {
            return [];
        }

        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection?->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $this->resolveDocBlockFunctionName($node),
            $docComment->getText()
        );

        $paramTags = $resolvedPhpDoc->getParamTags();

        $ruleErrors = [];
        foreach ($node->getParams() as $param) {
            if (null !== $param->type) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (!isset($paramTags[$param->var->name])) {
                continue;
            }

            $docType = $paramTags[$param->var->name]->getType();

            // a template "T" resolves to mixed too, but documents a generic contract, not a mixed value
            if ($docType instanceof TemplateType) {
                continue;
            }

            if (!$docType instanceof MixedType || !$docType->isExplicitMixed()) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s" has a "mixed" docblock, so it must declare the native "mixed" type.',
                $param->var->name,
                $node->name->toString().'()'
            ))
                ->identifier('mautic.nativeMixedTypeForMixedParamDocblock')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    // the docblock resolver keys a method by its bare name (the class provides the scope) but a plain
    // function by its fully-qualified name
    private function resolveDocBlockFunctionName(ClassMethod|Function_ $functionLike): string
    {
        if ($functionLike instanceof Function_ && null !== $functionLike->namespacedName) {
            return $functionLike->namespacedName->toString();
        }

        return $functionLike->name->toString();
    }
}
