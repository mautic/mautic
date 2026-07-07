<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AbstractSearchScopeProviderTest extends TestCase
{
    public function testGetScopesDeduplicatesPinnedAndDynamicCommands(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $provider = new class($translator) extends AbstractSearchScopeProvider {
            protected function getPinnedScopes(): array
            {
                return [
                    SearchScopePresets::standard(),
                    SearchScopePresets::isMine(),
                ];
            }

            protected function getAdditionalCommandKeys(): array
            {
                return ['mautic.core.searchcommand.ismine', 'custom_field'];
            }
        };

        $scopes   = $provider->getScopes();
        $commands = array_column($scopes, 'command');

        self::assertSame('', $scopes[0]['command']);
        self::assertTrue($scopes[0]['default'] ?? false);
        self::assertContains('is:mine', $commands);
        self::assertContains('custom_field', $commands);
        self::assertCount(count(array_unique($commands)), $commands);
    }
}
