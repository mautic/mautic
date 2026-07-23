<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\DateTimeHelper;

class DateTimeExtension
{
    public function __construct(
        private readonly DateTimeHelper $helper,
    ) {
    }

    /**
     * @see DateTimeHelper::getUtcDateTime
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'dateTimeGetUtcDateTime', isSafe: ['all'])]
    public function getUtcDateTime(): \DateTimeInterface
    {
        return $this->helper->getUtcDateTime();
    }
}
