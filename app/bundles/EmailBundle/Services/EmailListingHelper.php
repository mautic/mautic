<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Services;

use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailListingHelper
{
    public function __construct(
        private CorePermissions $security,
        private ListModel $leadListModel,
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
     * @return array{filters: array{placeholder: string, multiple: bool, groups: array<string, array<string, mixed>>}}
     */
    public function getListFilters(ThemeHelper $themeHelper): array
    {
        return [
            'filters' => [
                'placeholder' => $this->translator->trans('mautic.email.filter.placeholder'),
                'multiple'    => true,
                'groups'      => [
                    'mautic.core.filter.lists' => [
                        'options' => $this->leadListModel->getUserLists(),
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
     */
    public function applyCurrentFilters(array $currentFilters, array &$listFilters, array &$filter): bool
    {
        $ignoreListJoin = true;

        if ([] === $currentFilters) {
            return $ignoreListJoin;
        }

        $listAliases = [];
        $catIds      = [];
        $templates   = [];
        $searchTerms = [];

        foreach ($currentFilters as $type => $typeFilters) {
            $groupKey = $this->getListFilterGroupKey($type);
            if (null !== $groupKey) {
                $listFilters['filters']['groups'][$groupKey]['values'] = $typeFilters;
            }

            foreach ($typeFilters as $currentFilter) {
                switch ($type) {
                    case 'list':
                        $listAlias     = $this->getListAlias($currentFilter);
                        $listAliases[] = $listAlias;
                        $searchTerms[] = 'list:'.$currentFilter;
                        $searchTerms[] = 'list:'.$listAlias;
                        break;
                    case 'category':
                        $catIds[] = (int) $currentFilter;
                        $searchTerms[] = 'category:'.$currentFilter;
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

    private function getListAlias(string $listFilter): string
    {
        $listAliasLookup = array_column($this->leadListModel->getUserLists(), 'alias', 'id');

        return $listAliasLookup[(int) $listFilter] ?? $listFilter;
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
