<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CategoryBundle\Model\CategoryModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

trait CategoryListFiltersTrait
{
    private CategoryModel $categoryModel;

    #[Required]
    public function autowireCategoryListFiltersTrait(CategoryModel $categoryModel): void
    {
        $this->categoryModel = $categoryModel;
    }

    /**
     * @param array<string, mixed>      $filter
     * @param string|array<int, string> $categoryType
     *
     * @return array{filters: array<string, mixed>, categories: array<int|string, array<string, mixed>>, searchTerms: array<int, string>}
     */
    protected function applyCategoryListFilter(
        Request $request,
        string $sessionKey,
        string|array $categoryType,
        string $filterColumn,
        array &$filter,
        string $filterGroup = 'mautic.core.filter.categories',
        string $placeholder = 'mautic.core.category.filter.placeholder',
    ): array {
        $categoryTypes        = is_array($categoryType) ? $categoryType : [$categoryType];
        $categories           = [];
        foreach ($categoryTypes as $type) {
            $categories = array_merge($categories, $this->categoryModel->getLookupResults($type, '', 0));
        }
        $categoryFilterPrefix = $this->translator->trans('mautic.core.searchcommand.category');

        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans($placeholder),
                'multiple'    => true,
                'groups'      => [
                    $filterGroup => [
                        'options' => $categories,
                        'prefix'  => $categoryFilterPrefix,
                    ],
                ],
            ],
        ];

        $currentFilters = $this->getCurrentCategoryListFilters($request, $sessionKey);
        $request->getSession()->set($sessionKey, $currentFilters);

        $selectedCategoryValues = $this->getSelectedCategoryValues($currentFilters, $categoryFilterPrefix);
        if (!empty($selectedCategoryValues)) {
            $listFilters['filters']['groups'][$filterGroup]['values'] = $selectedCategoryValues;
        }

        $categoryIds = $this->getSelectedCategoryIds($selectedCategoryValues, $categories);
        if ([] !== $selectedCategoryValues) {
            $filter['force'][] = [
                'column' => $filterColumn,
                'expr'   => 'in',
                'value'  => $categoryIds ?: [0],
            ];
        }

        return [
            'filters'    => $listFilters,
            'categories' => $categories,
            'searchTerms' => $this->getCategorySearchTerms($currentFilters),
        ];
    }

    /**
     * @param array<string, array<int, string>> $currentFilters
     *
     * @return array<int, string>
     */
    private function getCategorySearchTerms(array $currentFilters): array
    {
        $searchTerms = [];
        foreach ($currentFilters as $type => $typeFilters) {
            foreach ($typeFilters as $typeFilter) {
                $searchTerms[] = $type.':'.$typeFilter;
            }
        }

        return $searchTerms;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getCurrentCategoryListFilters(Request $request, string $sessionKey): array
    {
        $currentFilters = $this->normalizeCategoryFilters($request->getSession()->get($sessionKey, []));
        $updatedFilters = $request->get('filters', false);

        if (!$updatedFilters) {
            $filters = $currentFilters;
        } elseif (!is_string($updatedFilters)) {
            $filters = [];
        } else {
            $decodedFilters = json_decode($updatedFilters, true);
            $filters        = [];

            if (is_array($decodedFilters)) {
                foreach ($decodedFilters as $updatedFilter) {
                    if (!is_string($updatedFilter) || !str_contains($updatedFilter, ':')) {
                        continue;
                    }

                    [$column, $value]   = explode(':', $updatedFilter, 2);
                    $filters[$column][] = $value;
                }
            }
        }

        return $filters;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function normalizeCategoryFilters(mixed $filters): array
    {
        if (!is_array($filters)) {
            return [];
        }

        $normalizedFilters = [];
        foreach ($filters as $type => $typeFilters) {
            if (!is_string($type) || !is_array($typeFilters)) {
                continue;
            }

            foreach ($typeFilters as $typeFilter) {
                if (is_scalar($typeFilter)) {
                    $normalizedFilters[$type][] = (string) $typeFilter;
                }
            }
        }

        return $normalizedFilters;
    }

    /**
     * @param array<int, string>                      $selectedCategoryValues
     * @param array<int|string, array<string, mixed>> $categories
     *
     * @return array<int>
     */
    private function getSelectedCategoryIds(array $selectedCategoryValues, array $categories): array
    {
        if ([] === $selectedCategoryValues) {
            return [];
        }

        $categoryIdsByAlias = [];
        foreach ($categories as $category) {
            if (!empty($category['alias'])) {
                $categoryIdsByAlias[$category['alias']] = (int) $category['id'];
            }
        }

        $categoryIds = [];
        foreach ($selectedCategoryValues as $filterValue) {
            if (is_numeric($filterValue)) {
                $categoryIds[] = (int) $filterValue;
                continue;
            }

            if (isset($categoryIdsByAlias[$filterValue])) {
                $categoryIds[] = $categoryIdsByAlias[$filterValue];
            }
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * @param array<string, array<int, string>> $currentFilters
     *
     * @return array<int, string>
     */
    private function getSelectedCategoryValues(array $currentFilters, string $categoryFilterPrefix): array
    {
        $selectedValues = [];
        foreach ($currentFilters as $type => $typeFilters) {
            if ($type === $categoryFilterPrefix) {
                $type = 'category';
            }

            if ('category' === $type) {
                $selectedValues = array_merge($selectedValues, $typeFilters);
            }
        }

        return $selectedValues;
    }
}
