<?php

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DateTimeExtension extends AbstractExtension
{
    private DateTimeHelper $helper;

    public function __construct(DateTimeHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
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
