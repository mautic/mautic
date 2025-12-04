<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;

class AnalyticsExtension
{
    public function __construct(
        protected AnalyticsHelper $helper,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction('analyticsGetCode', isSafe: ['all'])]
    public function getCode(): string
    {
        return (string) $this->helper->getCode();
    }
}
