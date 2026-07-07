<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\DynamicContentBundle\Helper\DynamicContentSearchScopeProvider;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DynamicContentSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $dynamicContentModel = $this->createMock(DynamicContentModel::class);
        $translator          = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.lang' => 'lang',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                default => $key,
            });

        $dynamicContentModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.lang',
                'mautic.core.searchcommand.ismine',
            ]);

        return new DynamicContentSearchScopeProvider($dynamicContentModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['lang'];
    }
}
