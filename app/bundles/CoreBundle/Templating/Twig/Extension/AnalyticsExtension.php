<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\AnalyticsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AnalyticsExtension extends AbstractExtension
{
    protected AnalyticsHelper $helper;

    public function __construct(AnalyticsHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
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
