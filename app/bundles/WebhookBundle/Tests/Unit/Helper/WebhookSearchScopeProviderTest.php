<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Helper;

use Mautic\WebhookBundle\Helper\WebhookSearchScopeProvider;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebhookSearchScopeProviderTest extends TestCase
{
    private WebhookModel&MockObject $webhookModel;

    private TranslatorInterface&MockObject $translator;

    private WebhookSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->webhookModel = $this->createMock(WebhookModel::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->webhookModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.core.searchcommand.ismine',
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new WebhookSearchScopeProvider(
            $this->webhookModel,
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

        $this->assertContains('name', $commands);
        $this->assertContains('is:mine', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
