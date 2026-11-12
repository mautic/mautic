<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Doctrine mapping must use attributes, not the static loadMetadata() method.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoLoadMetadataMethodRuleTest
 */
final class NoLoadMetadataMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->isStatic() || 'loadmetadata' !== $node->name->toLowerString()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message('Static "loadMetadata()" method is not allowed. Map the entity with Doctrine #[ORM\...] attributes instead.')
            ->identifier('mautic.noLoadMetadata')
            ->build();

        return [$ruleError];
    }
}
