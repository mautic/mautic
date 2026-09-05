<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller that defines its own constructor must not inject through a #[Required] method as well.
 *
 * The #[Required] setter only exists for controllers that inherit a wide constructor from a parent, where adding
 * a dependency would mean copying every parent param just to forward it. A controller that already has its own
 * constructor has no such problem, so the dependency belongs there, in a single place.
 *
 * Base and common controllers are the ones being extended, so they keep both ways open for their children.
 *
 * @implements Rule<Class_>
 */
final class NoRequiredMethodWithConstructorInControllerRule implements Rule
{
    private const string REQUIRED_ATTRIBUTE = \Symfony\Contracts\Service\Attribute\Required::class;

    private const string CONTROLLER_SUFFIX = 'Controller.php';

    /**
     * @var string[]
     */
    private const array PARENT_CONTROLLER_NAME_PARTS = ['Base', 'Common'];

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!str_ends_with($scope->getFile(), self::CONTROLLER_SUFFIX)) {
            return [];
        }

        // an abstract controller is extended, so its children may still need the setter
        if ($node->isAbstract()) {
            return [];
        }

        $shortClassName = $node->name instanceof Node\Identifier ? $node->name->toString() : null;
        if (null === $shortClassName || $this->isParentController($shortClassName)) {
            return [];
        }

        if (!$node->getMethod('__construct') instanceof ClassMethod) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->getMethods() as $classMethod) {
            if (!$this->hasRequiredAttribute($classMethod)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Method "%s()" has the #[Required] attribute, but "%s" defines its own constructor. Move the dependency to the constructor and drop the method.',
                $classMethod->name->toString(),
                $shortClassName
            ))
                ->identifier('mautic.noRequiredMethodWithConstructorInController')
                ->line($classMethod->getStartLine())
                ->nonIgnorable()
                ->build();
        }

        return $ruleErrors;
    }

    private function isParentController(string $shortClassName): bool
    {
        return array_any(self::PARENT_CONTROLLER_NAME_PARTS, fn (string $parentControllerNamePart): bool => str_contains($shortClassName, $parentControllerNamePart));
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
}
