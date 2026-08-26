<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class AnalyticsExtension
{
    public function __construct(
        private AnalyticsHelper $helper,
    ) {
    }

    #[AsTwigFunction(name: 'analyticsGetCode', isSafe: ['all'])]
    public function getCode(): string
    {
        return $this->helper->getCode();
    }
}
