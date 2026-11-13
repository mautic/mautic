<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ReturnObjectDocblockCollector;

/**
 * A method with an object-type return docblock but no native return type must declare that object as a native
 * return type and drop the redundant return tag.
 *
 * Adding a native return type to a method whose parent is untyped is legal, but removing one a typed parent
 * declares is a fatal error. So a method is reported only when every subclass that also declares it is promoted
 * to the same native type in this run; the whole downward family moves together or not at all. A parent or
 * interface left untyped is no obstacle.
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\ReturnDocblockToNativeTypeRuleTest
 */
final readonly class ReturnDocblockToNativeTypeRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
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
        /** @var array<string, list<array{class: string, method: string, line: int, candidate: array{type: string}|null}>> $collected */
        $collected = $node->get(ReturnObjectDocblockCollector::class);

        // method name => list of classes that declare it, to walk the downward family
        $classesByMethod = [];
        // class => method => native type an emittable candidate would promote to
        $emittableType = [];
        foreach ($collected as $entries) {
            foreach ($entries as $entry) {
                $classesByMethod[$entry['method']][] = $entry['class'];

                if (null === $entry['candidate']) {
                    continue;
                }

                if ($this->isEntityClass($entry['class']) || $this->isEntityClass($entry['candidate']['type'])) {
                    continue;
                }

                $emittableType[$entry['class']][$entry['method']] = $entry['candidate']['type'];
            }
        }

        $ruleErrors = [];
        foreach ($collected as $filePath => $entries) {
            foreach ($entries as $entry) {
                $type = $emittableType[$entry['class']][$entry['method']] ?? null;
                if (null === $type) {
                    continue;
                }

                if (!$this->subtreeFullyPromoted($entry['class'], $entry['method'], $type, $classesByMethod[$entry['method']] ?? [], $emittableType)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Method "%s()" has an "@return %s" docblock but no native return type. Declare ": %s" and drop the "@return" tag.',
                    $entry['method'],
                    $type,
                    $type
                ))
                    ->identifier('mautic.returnDocblockToNativeType')
                    ->file($filePath)
                    ->line($entry['line'])
                    ->build();
            }
        }

        return $ruleErrors;
    }

    // Doctrine entities live under an \Entity\ namespace. A getter on an entity backs a mapped,
    // possibly-unhydrated property, and a getter that returns an entity usually wraps a nullable
    // find/getEntity lookup, so a native object return type can fatal at runtime.
    private function isEntityClass(string $className): bool
    {
        return str_contains($className, '\\Entity\\');
    }

    /**
     * True when every subclass that also declares this method is promoted to the same native type, so the whole
     * downward family can move together. A subclass left untyped (or typed differently) would lose a return type
     * its now-typed parent declares, which is a fatal error, so that blocks the promotion.
     *
     * @param string[]                           $declaringClasses
     * @param array<string, array<string, string>> $emittableType
     */
    private function subtreeFullyPromoted(string $className, string $methodName, string $type, array $declaringClasses, array $emittableType): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        foreach ($declaringClasses as $declaringClass) {
            if ($declaringClass === $className) {
                continue;
            }

            if (!$this->reflectionProvider->hasClass($declaringClass)) {
                return false;
            }

            if (!$this->reflectionProvider->getClass($declaringClass)->isSubclassOfClass($classReflection)) {
                continue;
            }

            if (($emittableType[$declaringClass][$methodName] ?? null) !== $type) {
                return false;
            }
        }

        return true;
    }
}
