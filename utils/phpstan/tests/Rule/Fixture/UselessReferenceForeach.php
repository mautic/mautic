<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class UselessReferenceForeach
{
    /**
     * @param array<object> $widgets
     * @param array<int>    $ordering
     */
    public function mutatesObjectViaMethod(array $widgets, array $ordering): void
    {
        foreach ($widgets as &$widget) {
            if (isset($ordering[$widget->getId()])) {
                $widget->setOrdering((int) $ordering[$widget->getId()]);
            }
        }
    }

    /**
     * @param array<object> $nodes
     */
    public function mutatesObjectProperty(array $nodes): void
    {
        foreach ($nodes as &$node) {
            $node->depth = 1;
        }
    }
}
