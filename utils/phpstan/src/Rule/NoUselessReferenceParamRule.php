<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ByReferenceParamCollector;

/**
 * A by-reference parameter that is never written to does nothing: the caller can never observe a change.
 * Object/array mutation does not need a reference. Drop the "&".
 *
 * PHP forces an overriding method to keep the parent's by-reference-ness, so a single method cannot be
 * judged alone. The collector gathers every method's by-reference parameters across the analysis, and this
 * rule groups them by override family (the root declaration plus every override). Only when no method in the
 * family writes to the parameter is the reference useless; the error is then reported once, on the root.
 *
 * A family whose root is declared outside the analysed code (a framework or vendor contract) is left alone,
 * the override cannot drop the reference on its own.
 *
 * @implements Rule<CollectedDataNode>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoUselessReferenceParamRuleTest
 */
final class NoUselessReferenceParamRule implements Rule
{
    /**
     * @var array<string, string|null>
     */
    private array $rootClassCache = [];

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
        /** @var array<string, list<array{class: string, method: string, params: list<array{name: string, position: int, line: int, useless: bool}>}>> $collected */
        $collected = $node->get(ByReferenceParamCollector::class);

        $entriesByClassMethod = [];
        $uselessByFamilyPosition = [];
        foreach ($collected as $filePath => $entries) {
            foreach ($entries as $entry) {
                $classMethodKey = $this->classMethodKey($entry['class'], $entry['method']);
                $entriesByClassMethod[$classMethodKey] = ['file' => $filePath] + $entry;

                $rootClass = $this->resolveRootClass($entry['class'], $entry['method']);
                if (null === $rootClass) {
                    continue;
                }

                $familyKey = $this->classMethodKey($rootClass, $entry['method']);
                foreach ($entry['params'] as $param) {
                    $familyPositionKey = $familyKey.'#'.$param['position'];
                    $uselessByFamilyPosition[$familyPositionKey] = ($uselessByFamilyPosition[$familyPositionKey] ?? true) && $param['useless'];
                }
            }
        }

        $ruleErrors = [];
        foreach ($entriesByClassMethod as $entry) {
            $rootClass = $this->resolveRootClass($entry['class'], $entry['method']);
            if ($rootClass !== $entry['class']) {
                // report once, on the family root
                continue;
            }

            $familyKey = $this->classMethodKey($rootClass, $entry['method']);
            foreach ($entry['params'] as $param) {
                if (true !== ($uselessByFamilyPosition[$familyKey.'#'.$param['position']] ?? false)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Parameter "$%s" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                    $param['name']
                ))
                    ->identifier('mautic.noUselessReferenceParam')
                    ->file($entry['file'])
                    ->line($param['line'])
                    ->build();
            }
        }

        return $ruleErrors;
    }

    private function classMethodKey(string $class, string $method): string
    {
        return $class.'::'.strtolower($method);
    }

    /**
     * Returns the single top-most class or interface that declares the method natively, or null when it is
     * declared outside the analysed code or through more than one unrelated supertype (an ambiguous family).
     */
    private function resolveRootClass(string $classFqn, string $method): ?string
    {
        $cacheKey = $this->classMethodKey($classFqn, $method);
        if (array_key_exists($cacheKey, $this->rootClassCache)) {
            return $this->rootClassCache[$cacheKey];
        }

        if (!$this->reflectionProvider->hasClass($classFqn)) {
            return $this->rootClassCache[$cacheKey] = null;
        }

        $classReflection = $this->reflectionProvider->getClass($classFqn);

        $declarers = [];
        foreach ([$classReflection, ...$classReflection->getParents(), ...$classReflection->getInterfaces()] as $candidate) {
            if ($candidate->hasNativeMethod($method)) {
                $declarers[$candidate->getName()] = $candidate;
            }
        }

        if ([] === $declarers) {
            return $this->rootClassCache[$cacheKey] = $classFqn;
        }

        $roots = [];
        foreach ($declarers as $name => $declarer) {
            $hasDeclaringSupertype = false;
            foreach ([...$declarer->getParents(), ...$declarer->getInterfaces()] as $supertype) {
                if (isset($declarers[$supertype->getName()])) {
                    $hasDeclaringSupertype = true;
                    break;
                }
            }

            if (!$hasDeclaringSupertype) {
                $roots[] = $name;
            }
        }

        return $this->rootClassCache[$cacheKey] = 1 === count($roots) ? $roots[0] : null;
    }
}
