<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\ReportBundle\Helper\ReportSearchScopeProvider;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): ReportSearchScopeProvider
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

    public function testSourceCommandIsNotOfferedAsScope(): void
    {
        $reportModel = $this->createMock(ReportModel::class);
        $translator  = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.report.report.searchcommand.source' => 'source',
                default => $key,
            });

        $reportModel->method('getCommandList')
            ->willReturn([
                'mautic.report.report.searchcommand.source',
                'mautic.core.searchcommand.ismine',
            ]);

        $provider = new ReportSearchScopeProvider($reportModel, $translator);
        $commands = array_column($provider->getScopes(), 'command');

        $this->assertNotContains('source', $commands);
    }
}
