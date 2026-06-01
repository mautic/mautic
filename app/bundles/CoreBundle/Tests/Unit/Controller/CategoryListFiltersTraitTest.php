<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Controller;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\CategoryListFiltersTrait;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CategoryListFiltersTraitTest extends TestCase
{
    /**
     * @var array<int, array{id: int, alias: string, title: string}>
     */
    private const CATEGORIES = [
        ['id' => 11, 'alias' => 'first-category', 'title' => 'First category'],
        ['id' => 22, 'alias' => 'second-category', 'title' => 'Second category'],
    ];

    public function testAppliesCategoryFilterFromRequest(): void
    {
        $request = $this->createRequest([
            'filters' => json_encode(['category:first-category', 'category:22', 'category:first-category'], JSON_THROW_ON_ERROR),
        ]);
        $filter = $this->createFilter();

        $result = $this->createController()->apply(
            $request,
            'mautic.test.list_filters',
            'email',
            'cat.id',
            $filter
        );

        Assert::assertSame(
            [
                'category' => ['first-category', '22', 'first-category'],
            ],
            $request->getSession()->get('mautic.test.list_filters')
        );
        Assert::assertSame(
            [
                [
                    'column' => 'cat.id',
                    'expr'   => 'in',
                    'value'  => [11, 22],
                ],
            ],
            $filter['force']
        );
        Assert::assertSame(self::CATEGORIES, $result['categories']);
        Assert::assertSame('Filter by category', $result['filters']['filters']['placeholder']);
        Assert::assertSame(
            ['first-category', '22', 'first-category'],
            $result['filters']['filters']['groups']['mautic.core.filter.categories']['values']
        );
    }

    public function testUsesTranslatedCategoryPrefixFromStoredSessionFilters(): void
    {
        $request = $this->createRequest();
        $request->getSession()->set('mautic.test.list_filters', [
            'Category' => ['second-category'],
        ]);
        $filter = $this->createFilter();

        $this->createController()->apply(
            $request,
            'mautic.test.list_filters',
            'asset',
            'c.id',
            $filter
        );

        Assert::assertSame(
            [
                [
                    'column' => 'c.id',
                    'expr'   => 'in',
                    'value'  => [22],
                ],
            ],
            $filter['force']
        );
    }

    public function testClearsCategoryFiltersWhenRequestFilterPayloadIsEmpty(): void
    {
        $request = $this->createRequest([
            'filters' => '[]',
        ]);
        $request->getSession()->set('mautic.test.list_filters', [
            'category' => ['first-category'],
        ]);
        $filter = $this->createFilter();

        $result = $this->createController()->apply(
            $request,
            'mautic.test.list_filters',
            'email',
            'cat.id',
            $filter
        );

        Assert::assertSame([], $request->getSession()->get('mautic.test.list_filters'));
        Assert::assertSame([], $filter['force']);
        Assert::assertArrayNotHasKey(
            'values',
            $result['filters']['filters']['groups']['mautic.core.filter.categories']
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private function createRequest(array $query = []): Request
    {
        $request = new Request($query);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    /**
     * @return array{string: string, force: list<array<string, mixed>>}
     */
    private function createFilter(): array
    {
        return ['string' => '', 'force' => []];
    }

    private function createController(): object
    {
        $categoryModel = $this->createMock(CategoryModel::class);
        $categoryModel->method('getLookupResults')
            ->willReturn(self::CATEGORIES);

        $translator = $this->createMock(Translator::class);
        $translator->method('trans')
            ->willReturnMap([
                ['mautic.core.searchcommand.category', [], null, null, 'Category'],
                ['mautic.core.category.filter.placeholder', [], null, null, 'Filter by category'],
            ]);

        return new class($categoryModel, $translator) {
            use CategoryListFiltersTrait;

            public function __construct(
                private CategoryModel $categoryModel,
                public Translator $translator,
            ) {
            }

            public function getModel(string $name): CategoryModel
            {
                Assert::assertSame('category', $name);

                return $this->categoryModel;
            }

            /**
             * @param array<string, mixed> $filter
             *
             * @return array{filters: array<string, mixed>, categories: array<int|string, array<string, mixed>>}
             */
            public function apply(Request $request, string $sessionKey, string $categoryType, string $filterColumn, array &$filter): array
            {
                return $this->applyCategoryListFilter($request, $sessionKey, $categoryType, $filterColumn, $filter);
            }
        };
    }
}
