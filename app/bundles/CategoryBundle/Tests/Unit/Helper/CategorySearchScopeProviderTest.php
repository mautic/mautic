<?php

declare(strict_types=1);

namespace Mautic\CategoryBundle\Tests\Unit\Helper;

use Mautic\CategoryBundle\Helper\CategorySearchScopeProvider;
use Mautic\CategoryBundle\Model\CategoryModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CategorySearchScopeProviderTest extends TestCase
{
    private CategoryModel&MockObject $categoryModel;

    private TranslatorInterface&MockObject $translator;

    private CategorySearchScopeProvider $provider;

    protected function setUp(): void
    {
        $this->categoryModel = $this->createMock(CategoryModel::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);

        $this->translator->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'mautic.core.search.scope.standard' => 'Standard',
                'mautic.core.searchcommand.ispublished' => 'is:published',
                'mautic.core.searchcommand.isunpublished' => 'is:unpublished',
                'mautic.core.searchcommand.ids' => 'ids',
                default => $key,
            });

        $this->categoryModel->method('getCommandList')
            ->willReturn([
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.ids',
            ]);

        $this->provider = new CategorySearchScopeProvider(
            $this->categoryModel,
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

        $this->assertContains('is:published', $commands);
        $this->assertCount(count(array_unique($commands)), $commands);
    }
}
