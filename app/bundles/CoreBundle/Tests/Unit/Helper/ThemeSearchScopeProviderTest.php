<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ThemeSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ThemeSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): ThemeSearchScopeProvider
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.theme.searchcommand.feature' => 'feature',
                'mautic.core.theme.searchcommand.builder' => 'builder',
                default => $key,
            });

        return new ThemeSearchScopeProvider($translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['feature', 'builder'];
    }
}
