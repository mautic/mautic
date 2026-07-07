<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Unit\Helper;

use Mautic\ChannelBundle\Helper\MessageSearchScopeProvider;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MessageSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $messageModel = $this->createMock(MessageModel::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $messageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.ismine',
            ]);

        return new MessageSearchScopeProvider($messageModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
