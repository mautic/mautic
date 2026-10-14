<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;

/**
 * A param named "$entity" must declare a type, either a native type hint or a "@param" docblock tag.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\EntityParamMustHaveTypeRuleTest
 */
final readonly class EntityParamMustHaveTypeRule implements Rule
{
    private const PARAM_NAME = 'entity';

    public function __construct(
        private FileTypeMapper $fileTypeMapper
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
        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (self::PARAM_NAME !== strtolower($param->var->name)) {
                continue;
            }

            if ($param->type instanceof Node) {
                continue;
            }

            if ($this->hasParamTag($node, $scope)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" must have a type, either a native type or a "@param" tag.',
                $param->var->name,
                $node->name->toString()
            ))
                ->identifier('mautic.entityParamMustHaveType')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function hasParamTag(ClassMethod $classMethod, Scope $scope): bool
    {
        $docComment = $classMethod->getDocComment();
        if (null === $docComment) {
            return false;
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->isInClass() ? $scope->getClassReflection()->getName() : null,
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $classMethod->name->toString(),
            $docComment->getText()
        );

        return isset($resolvedPhpDoc->getParamTags()[self::PARAM_NAME]);
    }
}
