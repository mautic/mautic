<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Doctrine entities must map via PHP 8 attributes, not the static loadMetadata() StaticPHP driver.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class NoLoadMetadataMethodRule implements Rule
{
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
        if ('loadmetadata' !== $node->name->toLowerString()) {
            return [];
        }

        if (!$node->isStatic() || !$node->isPublic()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(
            'Entity must map via Doctrine attributes, not a static loadMetadata() method. Convert the mapping to #[ORM\\*] attributes and remove this method.'
        )
            ->identifier('mautic.noLoadMetadataMethod')
            ->build();

        return [$ruleError];
    }
}
