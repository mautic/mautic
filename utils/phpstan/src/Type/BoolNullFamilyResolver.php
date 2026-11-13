<?php

declare(strict_types=1);

namespace Utils\PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Decides which collected "bool|null" candidates can move to native "?bool" without breaking the family:
 * every non-private ancestor declaration must be a candidate too (an unanalysed one blocks), and every
 * descendant redeclaration must be a candidate or already carry a compatible native type.
 * A trait member is checked in each using class, but reported once.
 *
 * @phpstan-type Entry array{class: string, name: string, file: string, line: int, private: bool, native: string|null, candidate: bool}
 */
final readonly class BoolNullFamilyResolver
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private bool $isProperty
    ) {
    }

    /**
     * @param array<string, list<Entry>> $entriesByFile
     * @param list<string>               $compatibleNativeTypes
     *
     * @return list<array{file: string, line: int, name: string}>
     */
    public function resolveReportable(array $entriesByFile, array $compatibleNativeTypes): array
    {
        $entriesByName = [];
        foreach ($entriesByFile as $entries) {
            foreach ($entries as $entry) {
                $entriesByName[$entry['name']][$entry['class']] = $entry;
            }
        }

        // file:line:name => [name, every using-class context is safe]
        $locations = [];
        foreach ($entriesByFile as $entries) {
            foreach ($entries as $entry) {
                // a trait member is collected once per using class, but lives in the trait file,
                // so a non-candidate context blocks the shared declaration
                $file = $entry['file'];
                $key = $file.':'.$entry['line'].':'.$entry['name'];
                $isSafe = $entry['candidate'] && $this->isFamilySafe($entry, $entriesByName[$entry['name']], $compatibleNativeTypes);

                $locations[$key] = [
                    'file' => $file,
                    'line' => $entry['line'],
                    'name' => $entry['name'],
                    'safe' => $isSafe && ($locations[$key]['safe'] ?? true),
                ];
            }
        }

        $reportable = [];
        foreach ($locations as $location) {
            if ($location['safe']) {
                $reportable[] = ['file' => $location['file'], 'line' => $location['line'], 'name' => $location['name']];
            }
        }

        return $reportable;
    }

    /**
     * @param Entry                $entry
     * @param array<string, Entry> $sameNameEntries
     * @param list<string>         $compatibleNativeTypes
     */
    private function isFamilySafe(array $entry, array $sameNameEntries, array $compatibleNativeTypes): bool
    {
        if (!$this->reflectionProvider->hasClass($entry['class'])) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($entry['class']);
        $name = $entry['name'];

        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName() || $ancestor->isTrait()) {
                continue;
            }

            if (!$this->declaresNonPrivate($ancestor, $name)) {
                continue;
            }

            // an ancestor outside the analysed code, or one that is not a candidate, keeps its signature
            $ancestorEntry = $sameNameEntries[$ancestor->getName()] ?? null;
            if (null === $ancestorEntry || !$ancestorEntry['candidate']) {
                return false;
            }
        }

        if ($entry['private']) {
            return true;
        }

        foreach ($sameNameEntries as $className => $descendantEntry) {
            if ($className === $entry['class'] || !$this->reflectionProvider->hasClass($className)) {
                continue;
            }

            if (!in_array($entry['class'], $this->reflectionProvider->getClass($className)->getParentClassesNames(), true)
                && !$this->reflectionProvider->getClass($className)->implementsInterface($entry['class'])) {
                continue;
            }

            if ($descendantEntry['candidate'] || in_array($descendantEntry['native'], $compatibleNativeTypes, true)) {
                continue;
            }

            return false;
        }

        return true;
    }

    private function declaresNonPrivate(ClassReflection $classReflection, string $name): bool
    {
        if ($this->isProperty) {
            if (!$classReflection->hasNativeProperty($name)) {
                return false;
            }

            $property = $classReflection->getNativeProperty($name);

            return $property->getDeclaringClass()->getName() === $classReflection->getName() && !$property->isPrivate();
        }

        if (!$classReflection->hasNativeMethod($name)) {
            return false;
        }

        $method = $classReflection->getNativeMethod($name);

        return $method->getDeclaringClass()->getName() === $classReflection->getName() && !$method->isPrivate();
    }
}
