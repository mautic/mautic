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
 * Adding a native return type changes the whole override family at once, so it is only reported when the method
 * neither overrides a parent nor is overridden by a child in the analysed code. That leaves the loose,
 * polymorphic signatures of base classes alone.
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\ReturnDocblockToNativeTypeRuleTest
 */
final class ReturnDocblockToNativeTypeRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
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

        // method name => list of classes that declare it, to spot override families
        $classesByMethod = [];
        foreach ($collected as $entries) {
            foreach ($entries as $entry) {
                $classesByMethod[$entry['method']][] = $entry['class'];
            }
        }

        $ruleErrors = [];
        foreach ($collected as $filePath => $entries) {
            foreach ($entries as $entry) {
                if (null === $entry['candidate']) {
                    continue;
                }

                if ($this->isInOverrideFamily($entry['class'], $entry['method'], $classesByMethod[$entry['method']] ?? [])) {
                    continue;
                }

                $type = $entry['candidate']['type'];
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

    /**
     * @param string[] $declaringClasses
     */
    private function isInOverrideFamily(string $className, string $methodName, array $declaringClasses): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return true;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        foreach ($classReflection->getParents() as $parentReflection) {
            if ($parentReflection->hasNativeMethod($methodName)) {
                return true;
            }
        }

        foreach ($classReflection->getInterfaces() as $interfaceReflection) {
            if ($interfaceReflection->hasNativeMethod($methodName)) {
                return true;
            }
        }

        foreach ($declaringClasses as $declaringClass) {
            if ($declaringClass === $className) {
                continue;
            }

            if (!$this->reflectionProvider->hasClass($declaringClass)) {
                continue;
            }

            if ($this->reflectionProvider->getClass($declaringClass)->isSubclassOfClass($classReflection)) {
                return true;
            }
        }

        return false;
    }
}
