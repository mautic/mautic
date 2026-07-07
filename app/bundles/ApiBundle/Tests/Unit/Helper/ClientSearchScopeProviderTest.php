<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Unit\Helper;

use Mautic\ApiBundle\Helper\ClientSearchScopeProvider;
use Mautic\ApiBundle\Model\ClientModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientSearchScopeProviderTest extends TestCase
{
    private ClientModel&MockObject $clientModel;

    private TranslatorInterface&MockObject $translator;

    private ClientSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->clientModel = $this->createMock(ClientModel::class);
        $this->translator  = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.api.client.searchcommand.callback' => 'callback',
                'mautic.api.client.searchcommand.redirecturi' => 'redirecturi',
                default => $key,
            });

        $this->clientModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.api.client.searchcommand.callback',
                'mautic.api.client.searchcommand.redirecturi',
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new ClientSearchScopeProvider(
            $this->clientModel,
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

        $this->assertContains('callback', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
