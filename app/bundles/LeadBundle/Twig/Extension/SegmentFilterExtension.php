<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Segment\SegmentFilterIconTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SegmentFilterExtension extends AbstractExtension
{
    use SegmentFilterIconTrait;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSegmentFilterIcon', [$this, 'getSegmentFilterIcon']),
            new TwigFunction('dump', [$this, 'dump']),
        ];
    }

    public function dump($arg)
    {
        dump($arg);
    }
}
