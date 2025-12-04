<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;

class DncReasonExtension
{
    public function __construct(
        protected DncReasonHelper $helper,
    ) {
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @throws UnknownDncReasonException
     */
    #[\Twig\Attribute\AsTwigFunction('dncReasonToText')]
    public function toText(int $reasonId): string
    {
        return $this->helper->toText($reasonId);
    }
}
