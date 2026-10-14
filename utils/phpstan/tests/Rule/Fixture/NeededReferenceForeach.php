<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class NeededReferenceForeach
{
    /**
     * @param array<int> $values
     */
    public function reassignValue(array $values): void
    {
        foreach ($values as &$value) {
            $value = $value * 2;
        }
    }

    /**
     * @param array<array<string, int>> $rows
     */
    public function assignArrayElement(array $rows): void
    {
        foreach ($rows as &$row) {
            $row['depth'] = 1;
        }
    }

    /**
     * @param array<int> $counters
     */
    public function increment(array $counters): void
    {
        foreach ($counters as &$counter) {
            ++$counter;
        }
    }

    /**
     * @param array<string> $subjects
     */
    public function passedToByRefFunction(array $subjects): void
    {
        foreach ($subjects as &$subject) {
            preg_match('/\d+/', 'a1', $subject);
        }
    }

    /**
     * @param array<array<int>> $tree
     */
    public function nestedByReferenceForeach(array $tree): void
    {
        foreach ($tree as &$branch) {
            foreach ($branch as &$leaf) {
                $leaf = 0;
            }
        }
    }

    /**
     * @param array<array<string, mixed>> $groups
     */
    public function aliasesElementByReference(array $groups): void
    {
        foreach ($groups as &$group) {
            $inner = &$group['settings'];
            $inner['touched'] = true;
        }
    }

    /**
     * @param array<array<array<string, mixed>>> $groups
     */
    public function valueWalkedByNestedForeach(array $groups): void
    {
        foreach ($groups as &$group) {
            foreach ($group as $items) {
                foreach ($items as &$item) {
                    $item['seen'] = true;
                }
                unset($item);
            }
        }
        unset($group);
    }

    /**
     * @param array<int> $accumulators
     */
    public function capturedByReference(array $accumulators): void
    {
        foreach ($accumulators as &$accumulator) {
            $add = function () use (&$accumulator): void {
                ++$accumulator;
            };
            $add();
        }
    }
}
