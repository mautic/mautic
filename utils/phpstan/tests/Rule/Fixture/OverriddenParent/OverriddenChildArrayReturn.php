<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\OverriddenParent;

final class OverriddenChildArrayReturn extends AbstractDynamicLabelResolver
{
    protected function resolveDynamicLabel(string $commandKey, string $command): array
    {
        return [
            'label'   => $commandKey,
            'command' => $command,
        ];
    }
}
