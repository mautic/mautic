<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Services;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailListingHelper
{
    public function __construct(
        private CorePermissions $security,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, bool>
     */
    public function getEmailPermissions(): array
    {
        return $this->security->isGranted(
            [
                'email:emails:viewown',
                'email:emails:viewother',
                'email:emails:create',
                'email:emails:editown',
                'email:emails:editother',
                'email:emails:deleteown',
                'email:emails:deleteother',
                'email:emails:publishown',
                'email:emails:publishother',
            ],
            'RETURN_ARRAY'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $availableLists
     *
     * @return array{filters: array{placeholder: string, multiple: bool, groups: array<string, array<string, mixed>>}}
     */
    public function getListFilters(ThemeHelper $themeHelper, array $availableLists): array
    {
        return [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.email.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.lists' => [
                        'options' => array_column($availableLists, 'name', 'alias'),
                        'prefix'  => 'list',
                    ],
                    'mautic.core.filter.themes' => [
                        'options' => $themeHelper->getInstalledThemes('email'),
                        'prefix'  => 'theme',
                    ],
                ],
            ],
        ];
    }

    public function getStart(int $page, int $limit): int
    {
        return max(0, (1 === $page) ? 0 : (($page - 1) * $limit));
    }

    /**
     * @return array<string, list<string>>
     */
    public function getUpdatedFilters(string $updatedFilters): array
    {
        $decodedFilters = json_decode($updatedFilters, true);
        if (empty($decodedFilters)) {
            return [];
        }

        $newFilters = [];
        foreach ($decodedFilters as $updatedFilter) {
            [$column, $filter]     = explode(':', $updatedFilter);
            $newFilters[$column][] = $filter;
        }

        return $newFilters;
    }

    /**
     * @param array<string, list<string>>                                                                             $currentFilters
     * @param array{filters: array{placeholder: string, multiple: bool, groups: array<string, array<string, mixed>>}} $listFilters
     * @param array{string: string, force: array<int, array<string, mixed>>}                                          $filter
     * @param array<int, array<string, mixed>>                                                                        $categories
     * @param array<int, array<string, mixed>>                                                                        $availableLists
     */
    public function applyCurrentFilters(array $currentFilters, array &$listFilters, array &$filter, string $categoryFilterPrefix, array $categories, array $availableLists): bool
    {
        $ignoreListJoin = true;

        $listAliases = [];
        $catIds      = [];
        $templates   = [];
        $searchTerms = [];
        $listAliasLookup = array_column($availableLists, 'alias', 'id');
        $listAliasesByValue = array_column($availableLists, 'alias');

        foreach ($currentFilters as $type => $typeFilters) {
            foreach ($typeFilters as $typeFilter) {
                $searchTerms[] = $type.':'.$typeFilter;
            }

            if ($type === $categoryFilterPrefix) {
                $type = 'category';
            }

            $groupKey = $this->getListFilterGroupKey($type);
            if (null !== $groupKey) {
                $listFilters['filters']['groups'][$groupKey]['values'] = $typeFilters;
            }

            foreach ($typeFilters as $currentFilter) {
                switch ($type) {
                    case 'list':
                        $listAlias     = $this->getListAlias($currentFilter, $listAliasLookup, $listAliasesByValue);
                        $listAliases[] = $listAlias;
                        $searchTerms[] = 'list:'.$currentFilter;
                        $searchTerms[] = 'list:'.$listAlias;
                        break;
                    case 'category':
                        if (is_numeric($currentFilter)) {
                            $catIds[] = (int) $currentFilter;
                            break;
                        }

                        foreach ($categories as $category) {
                            if (($category['alias'] ?? null) === $currentFilter) {
                                $catIds[] = (int) $category['id'];
                                break;
                            }
                        }
                        break;
                    case 'theme':
                        $templates[] = $currentFilter;
                        $searchTerms[] = 'theme:'.$currentFilter;
                        break;
                }
            }
        }

        $filter['string'] = $this->stripQuickFilterTokensFromSearch($filter['string'], $searchTerms);

        if ([] !== $listAliases) {
            $filter['force'][] = ['column' => 'l.alias', 'expr' => 'in', 'value' => array_values(array_unique($listAliases))];
            $ignoreListJoin    = false;
        }

        if ([] !== $catIds) {
            $filter['force'][] = ['column' => 'c.id', 'expr' => 'in', 'value' => $catIds];
        }

        if ([] !== $templates) {
            $filter['force'][] = ['column' => 'e.template', 'expr' => 'in', 'value' => $templates];
        }

        return $ignoreListJoin;
    }

    public function getLastPageIfCurrentPageIsOutOfBounds(int $count, int $start, int $limit): ?int
    {
        if (!$count || $count >= ($start + 1)) {
            return null;
        }

        if (1 === $count) {
            return 1;
        }

        return (int) ((floor($count / $limit)) ?: 1);
    }

    private function getListFilterGroupKey(string $type): ?string
    {
        return match ($type) {
            'list'     => 'mautic.core.filter.lists',
            'category' => 'mautic.core.filter.categories',
            'theme'    => 'mautic.core.filter.themes',
            default    => null,
        };
    }

    /**
     * @param array<int, string> $listAliasLookup
     * @param list<string>       $listAliasesByValue
     */
    private function getListAlias(string $listFilter, array $listAliasLookup, array $listAliasesByValue): string
    {
        if (in_array($listFilter, $listAliasesByValue, true)) {
            return $listFilter;
        }

        return ctype_digit($listFilter) ? ($listAliasLookup[(int) $listFilter] ?? $listFilter) : $listFilter;
    }

    /**
     * @param list<string> $quickFilterTokens
     */
    private function stripQuickFilterTokensFromSearch(string $search, array $quickFilterTokens): string
    {
        if ([] === $quickFilterTokens) {
            return $search;
        }

        $searchTerms = preg_split('/\s+/', trim($search));
        if (false === $searchTerms) {
            return $search;
        }

        return implode(' ', array_diff($searchTerms, $quickFilterTokens));
    }
}
