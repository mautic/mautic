<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\BoolNullReturnDocblockCollector;
use Utils\PHPStan\Type\BoolNullFamilyResolver;

/**
 * A "@return bool|null" docblock must be a native "?bool" return type instead. Reported only when the whole
 * override family can move together (see BoolNullFamilyResolver).
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\BoolNullReturnDocblockToNativeTypeRuleTest
 */
final readonly class BoolNullReturnDocblockToNativeTypeRule implements Rule
{
    /**
     * Native return types a child may already declare under a "?bool" parent (covariance).
     *
     * @var list<string>
     */
    private const array COMPATIBLE_NATIVE_TYPES = ['bool', 'bool|null', 'false', 'false|null', 'true', 'null|true', 'null'];

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
        /** @var array<string, list<array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}>> $collected */
        $collected = $node->get(BoolNullReturnDocblockCollector::class);

        $familyResolver = new BoolNullFamilyResolver($this->reflectionProvider, false);

        $ruleErrors = [];
        foreach ($familyResolver->resolveReportable($collected, self::COMPATIBLE_NATIVE_TYPES) as $reportable) {
            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Method "%s()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                $reportable['name']
            ))
                ->identifier('mautic.boolNullReturnDocblockToNativeType')
                ->file($reportable['file'])
                ->line($reportable['line'])
                ->build();
        }

        return $ruleErrors;
    }
}
