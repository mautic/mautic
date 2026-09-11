<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Unit\Helper;

use Mautic\CampaignBundle\Helper\CampaignSearchScopeProvider;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): CampaignSearchScopeProvider
    {
        $campaignModel = $this->createMock(CampaignModel::class);
        $translator    = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $campaignModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
            ]);

        return new CampaignSearchScopeProvider($campaignModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
