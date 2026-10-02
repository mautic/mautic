<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Getter returning a to-many entity association must declare "Collection" return type.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\CollectionGetterMustReturnCollectionRuleTest
 */
final readonly class CollectionGetterMustReturnCollectionRule implements Rule
{
    private const array TO_MANY_ATTRIBUTES = [ManyToMany::class, OneToMany::class];

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
        if ($node->returnType instanceof Name && Collection::class === $node->returnType->toString()) {
            return [];
        }

        if (null === $node->stmts || !$scope->isInClass()) {
            return [];
        }

        $nativeReflection = $scope->getClassReflection()->getNativeReflection();

        $nodeFinder = new NodeFinder();

        /** @var Return_[] $returns */
        $returns = $nodeFinder->findInstanceOf($node->stmts, Return_::class);

        foreach ($returns as $return) {
            if (!$return->expr instanceof PropertyFetch) {
                continue;
            }

            if (!$return->expr->var instanceof Variable || 'this' !== $return->expr->var->name) {
                continue;
            }

            if (!$return->expr->name instanceof Identifier) {
                continue;
            }

            $propertyName = $return->expr->name->toString();
            if (!$nativeReflection->hasProperty($propertyName)) {
                continue;
            }

            if (!$this->hasToManyAttribute($nativeReflection->getProperty($propertyName)->getAttributes())) {
                continue;
            }

            $ruleError = RuleErrorBuilder::message(sprintf(
                'Method "%s()" returns to-many association "$%s", so it must declare "%s" return type.',
                $node->name->toString(),
                $propertyName,
                Collection::class
            ))
                ->identifier('mautic.collectionGetterMustReturnCollection')
                ->line($node->getStartLine())
                ->build();

            return [$ruleError];
        }

        return [];
    }

    /**
     * @param array<\ReflectionAttribute<object>> $attributes
     */
    private function hasToManyAttribute(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if (in_array($attribute->getName(), self::TO_MANY_ATTRIBUTES, true)) {
                return true;
            }
        }

        return false;
    }
}
