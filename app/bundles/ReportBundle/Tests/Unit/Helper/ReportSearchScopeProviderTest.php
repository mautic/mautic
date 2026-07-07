<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Unit\Helper;

use Mautic\ReportBundle\Helper\ReportSearchScopeProvider;
use Mautic\ReportBundle\Model\ReportModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReportSearchScopeProviderTest extends TestCase
{
    private ReportModel&MockObject $reportModel;

    private TranslatorInterface&MockObject $translator;

    private ReportSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->reportModel = $this->createMock(ReportModel::class);
        $this->translator  = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->reportModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.ismine',
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new ReportSearchScopeProvider(
            $this->reportModel,
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
