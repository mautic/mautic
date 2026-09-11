<?php

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DateTimeExtension extends AbstractExtension
{
    public function __construct(
        private readonly DateTimeHelper $helper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dateTimeGetUtcDateTime', $this->getUtcDateTime(...), ['is_safe' => ['all']]),
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
