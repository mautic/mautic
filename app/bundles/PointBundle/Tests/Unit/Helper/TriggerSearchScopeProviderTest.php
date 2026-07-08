<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PointBundle\Helper\TriggerSearchScopeProvider;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TriggerSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): TriggerSearchScopeProvider
    {
        $triggerModel = $this->createMock(TriggerModel::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.project.searchcommand.name' => 'project:name',
                default => $key,
            });

        $triggerModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
                'mautic.project.searchcommand.name',
            ]);

        return new TriggerSearchScopeProvider($triggerModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
