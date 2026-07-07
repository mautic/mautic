<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Unit\Helper;

use Mautic\PageBundle\Helper\PageSearchScopeProvider;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageSearchScopeProviderTest extends TestCase
{
    private PageModel&MockObject $pageModel;

    private TranslatorInterface&MockObject $translator;

    private PageSearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->pageModel  = $this->createMock(PageModel::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.lang' => 'lang',
                'mautic.page.searchcommand.isprefcenter' => 'is:prefcenter',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $this->pageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.lang',
                'mautic.page.searchcommand.isprefcenter',
                'mautic.core.searchcommand.ismine',
            ]);

        $this->provider = new PageSearchScopeProvider(
            $this->pageModel,
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

        $this->assertContains('is:prefcenter', $commands);
        $this->assertSame(count($commands), count(array_unique($commands)));
    }
}
