<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller method with the #[Required] attribute must be named "autowire" + the short name of the class it lives in.
 *
 * Symfony calls every #[Required] method on service instantiation. A parent and a child controller that both define
 * a method of the same name collide: the child silently overrides the parent, so the parent dependency is never
 * injected and the typed property stays uninitialized. Naming the method after its own class keeps it unique.
 *
 * Controllers extend each other deeply, which is where the collisions happen, so only they are checked.
 *
 * @implements Rule<ClassMethod>
 */
final class AutowireMethodNameMustMatchClassRule implements Rule
{
    /**
     * @var string
     */
    private const REQUIRED_ATTRIBUTE = 'Symfony\Contracts\Service\Attribute\Required';

    /**
     * @var string
     */
    private const AUTOWIRE_PREFIX = 'autowire';

    /**
     * @var string
     */
    private const CONTROLLER_SUFFIX = 'Controller.php';

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInController($scope)) {
            return [];
        }

        if (!$this->hasRequiredAttribute($node)) {
            return [];
        }

        $shortClassName = $this->resolveShortClassName($scope);
        if (null === $shortClassName) {
            return [];
        }

        $currentMethodName  = $node->name->toString();
        $expectedMethodName = self::AUTOWIRE_PREFIX.$shortClassName;

        if ($currentMethodName === $expectedMethodName) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Method "%s()" has the #[Required] attribute, so it must be named "%s()". Rename it to keep the autowired method unique in the class hierarchy.',
            $currentMethodName,
            $expectedMethodName
        ))
            ->identifier('mautic.autowireMethodName')
            ->build();

        return [$ruleError];
    }

    /**
     * The file that declares the method decides. Inside a trait the scope file is the class using the trait,
     * so a trait method would otherwise be checked once per controller using it.
     */
    private function isInController(Scope $scope): bool
    {
        if ($scope->isInTrait()) {
            $declaringFile = $scope->getTraitReflection()->getFileName();
        } else {
            $declaringFile = $scope->getFile();
        }

        if (null === $declaringFile) {
            return false;
        }

        return str_ends_with($declaringFile, self::CONTROLLER_SUFFIX);
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

    /**
     * A trait is named after itself, as the method is defined there, not in the classes using it.
     */
    private function resolveShortClassName(Scope $scope): ?string
    {
        if ($scope->isInTrait()) {
            return $scope->getTraitReflection()->getNativeReflection()->getShortName();
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return null;
        }

        // an anonymous class has no name to derive the method name from
        if ($classReflection->isAnonymous()) {
            return null;
        }

        return $classReflection->getNativeReflection()->getShortName();
    }
}
