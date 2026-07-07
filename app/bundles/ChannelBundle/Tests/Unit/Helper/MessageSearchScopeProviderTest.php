<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Unit\Helper;

use Mautic\ChannelBundle\Helper\MessageSearchScopeProvider;
use Mautic\ChannelBundle\Model\MessageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MessageSearchScopeProviderTest extends TestCase
{
    private MessageModel&MockObject $messageModel;

    private TranslatorInterface&MockObject $translator;

    private MessageSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->messageModel = $this->createMock(MessageModel::class);
        $this->translator   = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->messageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new MessageSearchScopeProvider(
            $this->messageModel,
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

        $this->assertContains('is:mine', $commands);
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
