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

    public function testGetScopesSortsIndentedCommandsAfterRegularOnes(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                default => $key,
            });

        $provider = new class($translator) extends AbstractSearchScopeProvider {
            protected function getPinnedScopes(): array
            {
                return [SearchScopePresets::standard()];
            }

            protected function getAdditionalCommandKeys(): array
            {
                return ['zzz_custom', 'aaa_regular', 'aaa_custom'];
            }

            protected function resolveDynamicLabel(string $commandKey, string $command): array
            {
                $indent = str_ends_with($commandKey, '_custom');

                return ['label' => $commandKey, 'translate' => false, 'indent' => $indent];
            }
        };

        $commands = array_column($provider->getScopes(), 'command');

        self::assertSame(['', 'aaa_regular', '__custom_fields__', 'aaa_custom', 'zzz_custom'], $commands);
    }

    public function testGetScopesInsertsDisabledCustomFieldsHeaderBeforeIndentedCommands(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                default => $key,
            });

        $provider = new class($translator) extends AbstractSearchScopeProvider {
            protected function getPinnedScopes(): array
            {
                return [SearchScopePresets::standard()];
            }

            protected function getAdditionalCommandKeys(): array
            {
                return ['aaa_regular', 'aaa_custom'];
            }

            protected function resolveDynamicLabel(string $commandKey, string $command): array
            {
                $indent = str_ends_with($commandKey, '_custom');

                return ['label' => $commandKey, 'translate' => false, 'indent' => $indent];
            }
        };

        $scopes = $provider->getScopes();
        $header = $scopes[array_search('__custom_fields__', array_column($scopes, 'command'), true)];

        self::assertSame('mautic.core.search.scope.custom_fields', $header['label']);
        self::assertTrue($header['translate']);
        self::assertTrue($header['disabled'] ?? false);
        self::assertArrayNotHasKey('indent', $header);
    }

    public function testGetScopesDoesNotInsertCustomFieldsHeaderWhenNoIndentedCommands(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                default => $key,
            });

        $provider = new class($translator) extends AbstractSearchScopeProvider {
            protected function getPinnedScopes(): array
            {
                return [SearchScopePresets::standard()];
            }

            protected function getAdditionalCommandKeys(): array
            {
                return ['aaa_regular', 'bbb_regular'];
            }
        };

        $commands = array_column($provider->getScopes(), 'command');

        self::assertNotContains('__custom_fields__', $commands);
    }
}
