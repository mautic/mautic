<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Mautic\PointBundle\Helper\PointSearchScopeProvider;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointSearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): AbstractSearchScopeProvider
    {
        $pointModel = $this->createMock(PointModel::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ismine' => 'is:mine',
                'mautic.project.searchcommand.name' => 'project:name',
                default => $key,
            });

        $pointModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ismine',
                'mautic.project.searchcommand.name',
            ]);

        return new PointSearchScopeProvider($pointModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:mine'];
    }
}
