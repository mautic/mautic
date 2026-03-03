<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AnalyticsExtension extends AbstractExtension
{
    public function __construct(
        protected AnalyticsHelper $helper,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('analyticsGetCode', [$this, 'getCode'], ['is_safe' => ['all']]),
        ];
    }

    public function getCode(): string
    {
        return (string) $this->helper->getCode();
    }
}
