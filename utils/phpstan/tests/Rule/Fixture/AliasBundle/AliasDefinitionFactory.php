<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\AliasBundle;

final class AliasDefinitionFactory
{
    /**
     * A service id asked for the Symfony reference way, e.g. in the "factory" of a definition array.
     *
     * @return array<string, mixed>
     */
    public function createDefinition(): array
    {
        return [
            'factory' => ['@mautic.alias.referenced_helper', 'someMethod'],
        ];
    }
}
