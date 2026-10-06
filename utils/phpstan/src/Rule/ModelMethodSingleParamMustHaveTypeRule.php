<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A single-parameter public method on a "*Model" class must declare that parameter's type.
 *
 * These methods are the model's public contract, e.g. "deleteEntity($entity)". A missing type hides what the
 * caller may pass and drops static checks, so the one parameter must be typed.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\ModelMethodSingleParamMustHaveTypeRuleTest
 */
final readonly class ModelMethodSingleParamMustHaveTypeRule implements Rule
{
    private const string MODEL_SUFFIX = 'Model';

    /**
     * @var string[]
     */
    private const array AMBIGUOUS_PARAMETER_NAMES = ['id', 'ids'];

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
        if (!$node->isPublic()) {
            return [];
        }

        if (1 !== count($node->params)) {
            return [];
        }

        if (null !== $node->params[0]->type) {
            return [];
        }

        $parameterName = $node->params[0]->var instanceof Node\Expr\Variable && is_string($node->params[0]->var->name)
            ? $node->params[0]->var->name
            : 'value';

        // "$id"/"$ids" carry no clear type - they may be an int, a string or a mix, so leave them alone
        if (in_array($parameterName, self::AMBIGUOUS_PARAMETER_NAMES, true)) {
            return [];
        }

        if (!$this->isInModelClass($scope)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Parameter "$%s" of model method "%s()" must declare a type.',
            $parameterName,
            $node->name->toString()
        ))
            ->identifier('mautic.modelMethodSingleParamMustHaveType')
            ->build();

        return [$ruleError];
    }

    private function isInModelClass(Scope $scope): bool
    {
        // inside a trait the scope class is the model using it, so the trait method would be checked repeatedly
        if ($scope->isInTrait()) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        return str_ends_with($classReflection->getName(), self::MODEL_SUFFIX);
    }
}
