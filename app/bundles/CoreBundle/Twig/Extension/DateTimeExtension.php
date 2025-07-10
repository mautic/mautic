<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DateTimeExtension extends AbstractExtension
{
    public function __construct(
        private DateTimeHelper $helper,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('dateTimeGetUtcDateTime', [$this, 'getUtcDateTime'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @see DateTimeHelper::getUtcDateTime
     */
    public function getUtcDateTime(): \DateTimeInterface
    {
        return $this->helper->getUtcDateTime();
    }
}
