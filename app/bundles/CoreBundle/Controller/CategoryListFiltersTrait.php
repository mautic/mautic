<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

trait CategoryListFiltersTrait
{
    /**
     * @param array<string, mixed> $filter
     *
     * @return array{filters: array<string, mixed>, categories: array<int|string, array<string, mixed>>}
     */
    protected function applyCategoryListFilter(Request $request, string $sessionKey, string $categoryType, string $filterColumn, array &$filter): array
    {
        /** @var \Mautic\CategoryBundle\Model\CategoryModel $categoryModel */
        $categoryModel        = $this->getModel('category');
        $categories           = $categoryModel->getLookupResults($categoryType, '', 0);
        $categoryFilterPrefix = $this->translator->trans('mautic.core.searchcommand.category');

        $listFilters = [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.core.category.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.categories' => [
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
            $listFilters['filters']['groups']['mautic.core.filter.categories']['values'] = $selectedCategoryValues;
        }

        $categoryIds = $this->getSelectedCategoryIds($selectedCategoryValues, $categories);
        if (!empty($categoryIds)) {
            $filter['force'][] = [
                'column' => $filterColumn,
                'expr'   => 'in',
                'value'  => $categoryIds,
            ];
        }

        return [
            'filters'    => $listFilters,
            'categories' => $categories,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getCurrentCategoryListFilters(Request $request, string $sessionKey): array
    {
        $currentFilters = $request->getSession()->get($sessionKey, []);
        $updatedFilters = $request->get('filters', false);

        if (!$updatedFilters) {
            return $currentFilters;
        }

        $decodedFilters = json_decode($updatedFilters, true);
        if (!$decodedFilters) {
            return [];
        }

        $newFilters = [];
        foreach ($decodedFilters as $updatedFilter) {
            [$column, $value]       = explode(':', $updatedFilter);
            $newFilters[$column][]  = $value;
        }

        return $newFilters;
    }

    /**
     * @param array<int, string>                      $selectedCategoryValues
     * @param array<int|string, array<string, mixed>> $categories
     *
     * @return array<int>
     */
    private function getSelectedCategoryIds(array $selectedCategoryValues, array $categories): array
    {
        if (empty($selectedCategoryValues)) {
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
