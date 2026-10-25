<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ObjectParamDocblockCollector;
use Utils\PHPStan\ServiceTypeDetector;

/**
 * An untyped parameter whose only type is an object-type param docblock must declare that object as a native
 * type instead.
 *
 * Promoting the docblock type to a native type restores static checks and lets the redundant docblock tag be
 * dropped.
 *
 * Adding a native type changes the whole override family at once, so a parameter is only reported when the
 * method neither overrides a parent nor is overridden by a child in the analysed code. That leaves the loose,
 * polymorphic signatures of base classes alone.
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\MoveObjectParamDocblockToNativeTypeRuleTest
 */
final class MoveObjectParamDocblockToNativeTypeRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceTypeDetector $serviceTypeDetector,
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
        /** @var array<string, list<array{class: string, method: string, candidates: list<array{name: string, type: string, line: int}>}>> $collected */
        $collected = $node->get(ObjectParamDocblockCollector::class);

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
                if ([] === $entry['candidates']) {
                    continue;
                }

                if ($this->isInOverrideFamily($entry['class'], $entry['method'], $classesByMethod[$entry['method']] ?? [])) {
                    continue;
                }

                foreach ($entry['candidates'] as $candidate) {
                    // a shared service must be injected, not promoted to a native parameter type; that is
                    // NoServiceInMethodParameterRule's domain
                    if ($this->serviceTypeDetector->isServiceType($candidate['type'])) {
                        continue;
                    }

                    // date objects stay in the docblock, native dates break nullable columns and hydration
                    if ($this->isDateTimeType($candidate['type'])) {
                        continue;
                    }

                    $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                        'Parameter "$%s" has an "@param %s" docblock. Declare "%s" as a native type and drop the "@param" tag.',
                        $candidate['name'],
                        $candidate['type'],
                        $candidate['type']
                    ))
                        ->identifier('mautic.moveObjectParamDocblockToNativeType')
                        ->file($filePath)
                        ->line($candidate['line'])
                        ->build();
                }
            }
        }

        return $ruleErrors;
    }

    private function isDateTimeType(string $type): bool
    {
        $type = ltrim($type, '\\');
        if (in_array($type, ['DateTime', 'DateTimeImmutable', 'DateTimeInterface'], true)) {
            return true;
        }

        if (!$this->reflectionProvider->hasClass($type)) {
            return false;
        }

        return $this->reflectionProvider->getClass($type)->implementsInterface(\DateTimeInterface::class);
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

        // the method overrides a parent: narrowing it would clash with the untyped parent signature
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

        // the method is overridden by a child present in the analysis
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
