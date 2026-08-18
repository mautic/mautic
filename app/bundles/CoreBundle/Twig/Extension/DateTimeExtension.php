<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class DateTimeExtension
{
    public function __construct(
        private DateTimeHelper $helper,
    ) {
    }

    /**
     * @see DateTimeHelper::getUtcDateTime
     */
    #[AsTwigFunction(name: 'dateTimeGetUtcDateTime', isSafe: ['all'])]
    public function getUtcDateTime(): \DateTime
    {
        return $this->helper->getUtcDateTime();
    }
}
