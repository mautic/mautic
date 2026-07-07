<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use PHPUnit\Framework\TestCase;

/**
 * Shared assertions for *SearchScopeProvider unit tests.
 *
 * Concrete tests only need to build the provider (with its mocked
 * dependencies) and, optionally, declare which dynamic commands are
 * expected to appear in the dropdown.
 */
abstract class SearchScopeProviderTestCase extends TestCase
{
    private AbstractSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->provider = $this->createProvider();
    }

    abstract protected function createProvider(): AbstractSearchScopeProvider;

    /**
     * @return list<string>
     */
    protected function expectedDynamicCommands(): array
    {
        return [];
    }

    public function testGetScopesIncludesStandardFirst(): void
    {
        $scopes = $this->getScopes();

        self::assertSame('', $scopes[0]['command']);
        self::assertSame('mautic.core.search.scope.standard', $scopes[0]['label']);
        self::assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $commands = array_column($this->getScopes(), 'command');

        foreach ($this->expectedDynamicCommands() as $expectedCommand) {
            self::assertContains($expectedCommand, $commands);
        }

        self::assertCount(count(array_unique($commands)), $commands);
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    protected function getScopes(): array
    {
        return $this->provider->getScopes();
    }
}
