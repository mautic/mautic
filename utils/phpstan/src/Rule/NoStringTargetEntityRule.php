<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A Doctrine association attribute must reference its target entity as a class constant
 * (Target::class), not a string. A string skips IDE navigation, refactoring and static analysis,
 * and hides typos until runtime.
 *
 * @implements Rule<Attribute>
 */
final class NoStringTargetEntityRule implements Rule
{
    /**
     * @var string[]
     */
    private const ASSOCIATION_ATTRIBUTES = [
        'Doctrine\ORM\Mapping\ManyToOne',
        'Doctrine\ORM\Mapping\OneToMany',
        'Doctrine\ORM\Mapping\OneToOne',
        'Doctrine\ORM\Mapping\ManyToMany',
    ];

    public function getNodeType(): string
    {
        return Attribute::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!in_array($node->name->toString(), self::ASSOCIATION_ATTRIBUTES, true)) {
            return [];
        }

        foreach ($node->args as $arg) {
            if (!$arg->name instanceof Node\Identifier || 'targetEntity' !== $arg->name->toString()) {
                continue;
            }

            if (!$arg->value instanceof String_) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Doctrine association #[%s] uses a string targetEntity "%s"; use %s::class instead.',
                    $node->name->getLast(),
                    $arg->value->value,
                    $arg->value->value,
                ))
                    ->identifier('mautic.noStringTargetEntity')
                    ->build(),
            ];
        }

        return [];
    }
}
