<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\UnionType as UnionTypeInstance;
use PHPStan\Type\VerbosityLevel;

/**
 * A param with a native union type must keep a union "@param" docblock, so the docblock cannot silently
 * drop a branch of the native type (e.g. native "object|array" documented as just "Lead").
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\NativeUnionParamMustHaveUnionDocblockRuleTest
 */
final readonly class NativeUnionParamMustHaveUnionDocblockRule implements Rule
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $unionParams = [];
        foreach ($node->params as $param) {
            if (!$param->type instanceof UnionType || !$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $unionParams[$param->var->name] = $param->type;
        }

        if ([] === $unionParams) {
            return [];
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->isInClass() ? $scope->getClassReflection()->getName() : null,
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $node->name->toString(),
            $docComment->getText()
        );

        $paramTags = $resolvedPhpDoc->getParamTags();

        $ruleErrors = [];
        foreach ($unionParams as $paramName => $nativeType) {
            $paramTag = $paramTags[$paramName] ?? null;

            // no "@param" means no narrowing docblock, the native union stands on its own
            if (null === $paramTag) {
                continue;
            }

            $docType = $paramTag->getType();
            if ($docType instanceof UnionTypeInstance || $docType instanceof MixedType) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" has native union type "%s" but its "@param" docblock type "%s" is not a union; the "@param" must also be a union.',
                $paramName,
                $node->name->toString(),
                $this->printNativeUnion($nativeType),
                $docType->describe(VerbosityLevel::typeOnly())
            ))
                ->identifier('mautic.nativeUnionParamMustHaveUnionDocblock')
                ->line($nativeType->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function printNativeUnion(UnionType $unionType): string
    {
        $names = [];
        foreach ($unionType->types as $subType) {
            if ($subType instanceof Identifier || $subType instanceof Name) {
                $names[] = $subType->toString();
            }
        }

        return implode('|', $names);
    }
}
