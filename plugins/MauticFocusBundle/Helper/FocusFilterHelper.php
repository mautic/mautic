<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;

class FocusFilterHelper
{
    public function __construct(
        private FocusRepository $focusRepository,
        private DynamicContentHelper $dynamicContentHelper,
    ) {
    }

    public function hasFilterBasedItems(): bool
    {
        return $this->focusRepository->hasPublishedWithFilters();
    }

    /**
     * Get ids of published, filter-based focus items whose filters match the given contact.
     *
     * @return int[]
     */
    public function getMatchingItemIds(Lead $lead): array
    {
        $rows = $this->focusRepository->getPublishedWithFilters();

        if (empty($rows)) {
            return [];
        }

        $leadArray = $this->dynamicContentHelper->convertLeadToArray($lead);
        $matched   = [];

        foreach ($rows as $row) {
            $filters = unserialize($row['filters']);

            if (!is_array($filters) || empty($filters)) {
                continue;
            }

            if ($this->dynamicContentHelper->filtersMatchContact($filters, $leadArray)) {
                $matched[] = (int) $row['id'];
            }
        }

        return $matched;
    }
}
