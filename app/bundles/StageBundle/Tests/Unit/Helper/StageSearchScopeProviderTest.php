<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\StageBundle\Helper\StageSearchScopeProvider;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class StageSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $stageModel = $this->createMock(StageModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.project.searchcommand.name' => 'project:name',
                default => $key,
            });

        $stageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
                'mautic.project.searchcommand.name',
            ]);

        return new StageSearchScopeProvider($stageModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
