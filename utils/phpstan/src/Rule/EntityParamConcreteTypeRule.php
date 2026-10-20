<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * On a "*Model" or "*Repository" class, a param named "$entity" must resolve to a concrete entity class,
 * not just "object". When a parent contract forces the native "object" type, narrow it with a "@param" tag,
 * e.g. "@param Category $entity". Test methods are exempt.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\EntityParamConcreteTypeRuleTest
 */
final readonly class EntityParamConcreteTypeRule implements Rule
{
    private const string PARAM_NAME = 'entity';

    public function __construct(
        private FileTypeMapper $fileTypeMapper
    )
    {
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
        // inside a trait the scope class is the model using it, so the trait method would be checked repeatedly
        if ($scope->isInTrait() || !$scope->isInClass()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (!$this->isModelOrRepository($classReflection)) {
            return [];
        }

        if ($this->isTestMethod($node, $classReflection)) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (self::PARAM_NAME !== strtolower($param->var->name)) {
                continue;
            }

            if ($this->hasConcreteNativeType($param, $scope) || $this->hasConcreteParamTag($node, $scope, $param->var->name)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" must declare the concrete entity class, not just "object". When a parent contract forces "object", narrow it with a "@param" tag.',
                $param->var->name,
                $node->name->toString()
            ))
                ->identifier('mautic.entityParamConcreteType')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function hasConcreteNativeType(Node\Param $param, Scope $scope): bool
    {
        if (!$param->type instanceof Node) {
            return false;
        }

        $nativeType = ParserNodeTypeToPHPStanType::resolve(
            $param->type,
            $scope->isInClass() ? $scope->getClassReflection() : null
        );

        return $this->namesConcreteClass($nativeType);
    }

    private function hasConcreteParamTag(ClassMethod $classMethod, Scope $scope, string $paramName): bool
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

        $paramTags = $resolvedPhpDoc->getParamTags();
        if (!isset($paramTags[$paramName])) {
            return false;
        }

        return $this->namesConcreteClass($paramTags[$paramName]->getType());
    }

    /**
     * True when the type names at least one concrete class, e.g. "Category", "Category|null" or "Lead|int".
     * A bare "object", "object|array" or "mixed" names none.
     */
    private function namesConcreteClass(Type $type): bool
    {
        $innerTypes = $type instanceof UnionType ? $type->getTypes() : [$type];

        foreach ($innerTypes as $innerType) {
            if ($innerType->getObjectClassNames() !== []) {
                return true;
            }
        }

        return false;
    }

    private function isModelOrRepository(ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();

        return str_ends_with($className, 'Model') || str_ends_with($className, 'Repository');
    }

    private function isTestMethod(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();
        if (!str_ends_with($className, 'Test') && !str_contains($className, '\\Tests\\')) {
            return false;
        }

        if (str_starts_with(strtolower($classMethod->name->toString()), 'test')) {
            return true;
        }

        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (str_ends_with($attr->name->toString(), 'Test')) {
                    return true;
                }
            }
        }

        return false;
    }
}
