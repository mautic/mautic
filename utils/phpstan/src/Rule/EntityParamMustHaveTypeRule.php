<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\UnionType;

/**
 * A param named "$entity" must have a native object type hint, either "object", a class/interface,
 * or a union that includes one (e.g. "object|array"). A docblock "@param" tag is not enough.
 * Test methods are exempt.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\EntityParamMustHaveTypeRuleTest
 */
final readonly class EntityParamMustHaveTypeRule implements Rule
{
    private const string PARAM_NAME = 'entity';

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
        if ($this->isTestMethod($node, $scope)) {
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

            if ($this->hasNativeObjectType($param, $scope)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" must have a native object type hint ("object", a class, or a union that includes one), not just a "@param" tag.',
                $param->var->name,
                $node->name->toString()
            ))
                ->identifier('mautic.entityParamMustHaveType')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function hasNativeObjectType(Node\Param $param, Scope $scope): bool
    {
        if (!$param->type instanceof Node) {
            return false;
        }

        $nativeType = ParserNodeTypeToPHPStanType::resolve(
            $param->type,
            $scope->isInClass() ? $scope->getClassReflection() : null
        );

        // a union like "object|array" is fine as long as one member is an object
        $innerTypes = $nativeType instanceof UnionType ? $nativeType->getTypes() : [$nativeType];

        foreach ($innerTypes as $innerType) {
            if ($innerType->isObject()->yes()) {
                return true;
            }
        }

        return false;
    }

    private function isTestMethod(ClassMethod $classMethod, Scope $scope): bool
    {
        if (!$scope->isInClass()) {
            return false;
        }

        $className = $scope->getClassReflection()->getName();
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
