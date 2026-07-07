<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\ReportBundle\Helper\ReportSearchScopeProvider;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $reportModel = $this->createMock(ReportModel::class);
        $translator  = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $reportModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.ismine',
                'mautic.core.searchcommand.ids',
            ]);

        return new ReportSearchScopeProvider($reportModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
