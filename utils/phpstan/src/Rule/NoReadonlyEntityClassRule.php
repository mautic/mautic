<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A Doctrine entity is hydrated via reflection without the constructor, so a readonly class breaks
 * loading and proxying. An entity is recognized by a public static loadMetadata() method.
 *
 * @implements Rule<Class_>
 */
final readonly class NoReadonlyEntityClassRule implements Rule
{
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
        if (!$node->isReadonly()) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $loadMetadataMethod = $node->getMethod('loadMetadata');
        if (!$loadMetadataMethod instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        if (!$loadMetadataMethod->isPublic()) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Entity class "%s" must not be readonly. Doctrine hydrates entities via reflection without the constructor, which a readonly class forbids. Remove the readonly modifier from the class.',
            (string) $node->namespacedName
        ))
            ->identifier('mautic.noReadonlyEntity')
            ->build();

        return [$ruleError];
    }
}
