<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

/**
 * Aggregate analytics are totals, like how many times a form was submitted, that are not linked to any contact.
 * Features that record such totals check this before they do, independently of whether contacts are tracked.
 */
final readonly class AggregateAnalyticsHelper
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('aggregate_analytics', true);
    }
}
