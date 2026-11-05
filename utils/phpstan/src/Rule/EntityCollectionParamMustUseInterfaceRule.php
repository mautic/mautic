<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PhpParser\Node;
use PhpParser\Node\Param;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ParserNodeTypeToPHPStanType;

/**
 * An entity param typed with the concrete "ArrayCollection" forces callers to that one implementation. Type the
 * param against the "Collection" interface instead, so any collection can be passed.
 *
 * @implements Rule<Param>
 *
 * @see \Utils\PHPStan\Tests\Rule\EntityCollectionParamMustUseInterfaceRuleTest
 */
final class EntityCollectionParamMustUseInterfaceRule implements Rule
{
    public function getNodeType(): string
    {
        return Param::class;
    }

    /**
     * @param Param $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }

        if (!str_contains($scope->getClassReflection()->getName(), '\\Entity\\')) {
            return [];
        }

        if (!$node->type instanceof Node) {
            return [];
        }

        $paramType = ParserNodeTypeToPHPStanType::resolve($node->type, $scope->getClassReflection());
        if ([ArrayCollection::class] !== $paramType->getObjectClassNames()) {
            return [];
        }

        $parameterName = $node->var instanceof Node\Expr\Variable && is_string($node->var->name)
            ? $node->var->name
            : 'value';

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Param "$%s" is typed as "ArrayCollection". Type it against the "%s" interface instead.',
            $parameterName,
            Collection::class
        ))
            ->identifier('mautic.entityCollectionParamInterface')
            ->line($node->getStartLine())
            ->build();

        return [$ruleError];
    }
}
