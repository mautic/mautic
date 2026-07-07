<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Unit\Helper;

use Mautic\CampaignBundle\Helper\CampaignSearchScopeProvider;
use Mautic\CampaignBundle\Model\CampaignModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignSearchScopeProviderTest extends TestCase
{
    private CampaignModel&MockObject $campaignModel;

    private TranslatorInterface&MockObject $translator;

    private CampaignSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->campaignModel = $this->createMock(CampaignModel::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.campaign.campaign.searchcommand.isexpired' => 'is:expired',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->campaignModel->method('getCommandList')
            ->willReturn([
                'mautic.campaign.campaign.searchcommand.isexpired',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new CampaignSearchScopeProvider(
            $this->campaignModel,
            $this->translator
        );
    }

    public function testGetScopesIncludesStandardFirst(): void
    {
        $scopes = $this->provider->getScopes();

        $this->assertSame('', $scopes[0]['command']);
        $this->assertSame('mautic.core.search.scope.standard', $scopes[0]['label']);
        $this->assertTrue($scopes[0]['default'] ?? false);
    }

    public function testGetScopesDoesNotDuplicatePinnedCommands(): void
    {
        $scopes = $this->provider->getScopes();

        $commands = array_column($scopes, 'command');

        $this->assertContains('is:expired', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
