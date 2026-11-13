<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\BoolNullPropertyDocblockCollector;
use Utils\PHPStan\Type\BoolNullFamilyResolver;

/**
 * A "@var bool|null" property docblock must be a native "?bool" type instead. Reported only when the whole
 * redeclaration family can move together (see BoolNullFamilyResolver).
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\BoolNullPropertyDocblockToNativeTypeRuleTest
 */
final readonly class BoolNullPropertyDocblockToNativeTypeRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var array<string, list<list<array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}>>> $collected */
        $collected = $node->get(BoolNullPropertyDocblockCollector::class);

        // one collected item per property statement, which may declare several properties
        $entriesByFile = array_map(static fn (array $items): array => array_merge(...$items), $collected);

        $familyResolver = new BoolNullFamilyResolver($this->reflectionProvider, true);

        $ruleErrors = [];
        // a typed property must be redeclared with exactly the same type
        foreach ($familyResolver->resolveReportable($entriesByFile, ['bool|null']) as $reportable) {
            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Property "$%s" has a "bool|null" docblock, so it must declare the native "?bool" type instead, with a "= null" default if it has none.',
                $reportable['name']
            ))
                ->identifier('mautic.boolNullPropertyDocblockToNativeType')
                ->file($reportable['file'])
                ->line($reportable['line'])
                ->build();
        }

        return $ruleErrors;
    }
}
