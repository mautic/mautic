<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PointBundle\Helper\PointInsightSearchScopeProvider;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointInsightSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): PointInsightSearchScopeProvider
    {
        $insightModel = $this->createMock(InsightModel::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $insightModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
            ]);

        return new PointInsightSearchScopeProvider($insightModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
