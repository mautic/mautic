<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PageBundle\Helper\PageSearchScopeProvider;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PageSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $pageModel  = $this->createMock(PageModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.lang' => 'lang',
                'mautic.page.searchcommand.isprefcenter' => 'is:prefcenter',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $pageModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.lang',
                'mautic.page.searchcommand.isprefcenter',
                'mautic.core.searchcommand.ismine',
            ]);

        return new PageSearchScopeProvider($pageModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:prefcenter'];
    }
}
