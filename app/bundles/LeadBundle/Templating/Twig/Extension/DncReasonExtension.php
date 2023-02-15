<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Templating\Twig\Extension;

use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Templating\Helper\DncReasonHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DncReasonExtension extends AbstractExtension
{
    /**
     * @var DncReasonHelper
     */
    protected $helper;

    public function __construct(DncReasonHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('dncReasonToText', [$this, 'toText']),
        ];
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @throws UnknownDncReasonException
     */
    public function toText(int $reasonId): string
    {
        return $this->helper->toText($reasonId);
    }
}
