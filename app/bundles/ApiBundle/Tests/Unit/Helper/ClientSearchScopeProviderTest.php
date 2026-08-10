<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Unit\Helper;

use Mautic\ApiBundle\Helper\ClientSearchScopeProvider;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ClientSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): ClientSearchScopeProvider
    {
        $clientModel = $this->createMock(FormModel::class);
        $translator  = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.api.client.searchcommand.callback' => 'callback',
                'mautic.api.client.searchcommand.redirecturi' => 'redirecturi',
                default => $key,
            });

        $clientModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.api.client.searchcommand.callback',
                'mautic.api.client.searchcommand.redirecturi',
                'mautic.core.searchcommand.ids',
            ]);

        return new ClientSearchScopeProvider($clientModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['callback'];
    }
}
