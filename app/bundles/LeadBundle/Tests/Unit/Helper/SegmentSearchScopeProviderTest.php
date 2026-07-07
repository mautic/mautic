<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Unit\Helper;

use Mautic\LeadBundle\Helper\SegmentSearchScopeProvider;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSearchScopeProviderTest extends TestCase
{
    private ListModel&MockObject $listModel;

    private TranslatorInterface&MockObject $translator;

    private SegmentSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->listModel  = $this->createMock(ListModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.name' => 'name',
                'mautic.lead.list.searchcommand.isglobal' => 'is:global',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->listModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.name',
                'mautic.lead.list.searchcommand.isglobal',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new SegmentSearchScopeProvider(
            $this->listModel,
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

        $this->assertContains('is:global', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
