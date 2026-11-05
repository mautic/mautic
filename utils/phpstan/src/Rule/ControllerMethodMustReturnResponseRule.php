<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller action must declare a Response return type.
 *
 * An action answers an HTTP request, so it hands a Response back - either alone or as one part of a union. A missing
 * or non-Response return type hides what the action really produces, and lets a docblock claim a narrower type than
 * the code returns.
 *
 * Only the public "*Action()" methods and "__invoke()" are actions. The public helpers a controller exposes next to
 * them - "getModelName()", "getViewArguments()" and the like - return their own data and are left alone.
 *
 * The shared base controllers - those with "Abstract" or "Common" in their name, e.g. "AbstractFormController" or
 * "FetchCommonApiController" - are skipped. Their actions are widened by child controllers, so a narrow return type
 * on the parent would lock the children out.
 *
 * @implements Rule<ClassMethod>
 */
final class ControllerMethodMustReturnResponseRule implements Rule
{
    /**
     * @var string
     */
    private const RESPONSE_CLASS = 'Symfony\\Component\\HttpFoundation\\Response';

    /**
     * @var string
     */
    private const CONTROLLER_SUFFIX = 'Controller';

    /**
     * @var string
     */
    private const ACTION_SUFFIX = 'action';

    /**
     * @var string
     */
    private const INVOKE_METHOD = '__invoke';

    /**
     * @var string[]
     */
    private const SKIPPED_CLASS_NAME_PARTS = ['Abstract', 'Common'];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
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
        if (!$node->isPublic()) {
            return [];
        }

        if (!$this->isAction($node)) {
            return [];
        }

        if (!$this->isInController($scope)) {
            return [];
        }

        if ($this->hasResponseReturnType($node->returnType, $scope)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Controller action "%s()" must return a Response, either alone or as part of a union.',
            $node->name->toString()
        ))
            ->identifier('mautic.controllerMethodMustReturnResponse')
            ->build();

        return [$ruleError];
    }

    private function isInController(Scope $scope): bool
    {
        // inside a trait the scope class is the controller using it, so the trait method would be checked repeatedly
        if ($scope->isInTrait()) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        $className = $classReflection->getName();
        if (!str_ends_with($className, self::CONTROLLER_SUFFIX)) {
            return false;
        }

        return !$this->isSharedBaseController($className);
    }

    private function isSharedBaseController(string $className): bool
    {
        $lastSeparatorPosition = strrpos($className, '\\');
        $shortClassName        = false === $lastSeparatorPosition ? $className : substr($className, $lastSeparatorPosition + 1);

        foreach (self::SKIPPED_CLASS_NAME_PARTS as $skippedClassNamePart) {
            if (str_contains($shortClassName, $skippedClassNamePart)) {
                return true;
            }
        }

        return false;
    }

    private function isAction(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toLowerString();

        return str_ends_with($methodName, self::ACTION_SUFFIX) || self::INVOKE_METHOD === $methodName;
    }

    private function hasResponseReturnType(Node\Identifier|Name|ComplexType|null $type, Scope $scope): bool
    {
        if ($type instanceof Name) {
            return $this->isResponseClass($scope->resolveName($type));
        }

        if ($type instanceof NullableType) {
            return $this->hasResponseReturnType($type->type, $scope);
        }

        if ($type instanceof UnionType) {
            foreach ($type->types as $unionedType) {
                if ($this->hasResponseReturnType($unionedType, $scope)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isResponseClass(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        return $this->reflectionProvider->getClass($className)->is(self::RESPONSE_CLASS);
    }
}
