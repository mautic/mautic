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
 * Only route actions - "*Action()" and "__invoke()" - can be public in a controller.
 *
 * Magic methods, #[Required] setters, methods required by a parent class or interface and event subscriber
 * listeners are skipped.
 *
 * @implements Rule<ClassMethod>
 */
final class NoPublicNonActionMethodInControllerRule implements Rule
{
    private const string CONTROLLER_SUFFIX = 'Controller';

    private const string ACTION_SUFFIX = 'action';

    private const string REQUIRED_ATTRIBUTE = \Symfony\Contracts\Service\Attribute\Required::class;

    private const string EVENT_SUBSCRIBER_INTERFACE = \Symfony\Component\EventDispatcher\EventSubscriberInterface::class;

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

        // inside a trait the scope class is the controller using it, so the trait method would be checked repeatedly
        if ($scope->isInTrait()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection || !str_ends_with($classReflection->getName(), self::CONTROLLER_SUFFIX)) {
            return [];
        }

        // listener methods are called by the event dispatcher
        if ($classReflection->implementsInterface(self::EVENT_SUBSCRIBER_INTERFACE)) {
            return [];
        }

        $methodName = $node->name->toString();
        if (str_starts_with($methodName, '__') || str_ends_with(strtolower($methodName), self::ACTION_SUFFIX)) {
            return [];
        }

        if ($this->hasRequiredAttribute($node) || $this->isInheritedContract($classReflection, $methodName)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Controller method "%s()" is not a route action, so it cannot be public. Make it protected or private.',
            $methodName
        ))
            ->identifier('mautic.noPublicNonActionMethodInController')
            ->build();

        return [$ruleError];
    }

    private function hasRequiredAttribute(ClassMethod $classMethod): bool
    {
        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::REQUIRED_ATTRIBUTE === $attr->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isInheritedContract(ClassReflection $classReflection, string $methodName): bool
    {
        $parentClassReflection = $classReflection->getParentClass();
        if ($parentClassReflection instanceof ClassReflection && $parentClassReflection->hasNativeMethod($methodName)) {
            return true;
        }

        return array_any($classReflection->getInterfaces(), fn (ClassReflection $interfaceReflection): bool => $interfaceReflection->hasNativeMethod($methodName));
    }
}
