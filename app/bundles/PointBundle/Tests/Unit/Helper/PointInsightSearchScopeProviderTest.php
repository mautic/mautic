<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PointBundle\Helper\PointInsightSearchScopeProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointInsightSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): PointInsightSearchScopeProvider
    {
        $insightModel = $this->createMock(CommonFormModel::class);
        $translator   = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $insightModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ids',
            ]);

        return new PointInsightSearchScopeProvider($insightModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return [];
    }
}
