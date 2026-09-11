<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use MauticPlugin\MauticFocusBundle\Helper\FocusSearchScopeProvider;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FocusSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): FocusSearchScopeProvider
    {
        $focusModel = $this->createMock(FocusModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.category' => 'category',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $focusModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.category',
                'mautic.core.searchcommand.ismine',
            ]);

        return new FocusSearchScopeProvider($focusModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['category'];
    }
}
