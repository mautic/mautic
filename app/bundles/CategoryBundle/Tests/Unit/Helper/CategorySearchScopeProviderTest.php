<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Tests\Unit\Helper;

use Mautic\CategoryBundle\Helper\CategorySearchScopeProvider;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Tests\Unit\Helper\SearchScopeProviderTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategorySearchScopeProviderTest extends SearchScopeProviderTestCase
{
    protected function createProvider(): CategorySearchScopeProvider
    {
        $categoryModel = $this->createMock(CategoryModel::class);
        $translator    = $this->createMock(TranslatorInterface::class);

        $translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ispublished' => 'is:published',
                'mautic.core.searchcommand.isunpublished' => 'is:unpublished',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $categoryModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.ids',
            ]);

        return new CategorySearchScopeProvider($categoryModel, $translator);
    }

    protected function expectedDynamicCommands(): array
    {
        return ['is:published'];
    }
}
