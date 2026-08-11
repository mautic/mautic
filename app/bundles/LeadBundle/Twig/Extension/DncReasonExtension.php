<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class DncReasonExtension
{
    public function __construct(
        private DncReasonHelper $helper,
    ) {
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @throws UnknownDncReasonException
     */
    #[AsTwigFunction(name: 'dncReasonToText')]
    public function toText(int $reasonId): string
    {
        return $this->helper->toText($reasonId);
    }
}
