<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\WebhookBundle\Helper\WebhookSearchScopeProvider;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): WebhookSearchScopeProvider
    {
        $webhookModel = $this->createMock(WebhookModel::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $webhookModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.core.searchcommand.ismine',
                'mautic.core.searchcommand.ids',
            ]);

        return new WebhookSearchScopeProvider($webhookModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['name', 'is:mine'];
    }
}
