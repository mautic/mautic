<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Templating\Twig\Extension;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ChannelExtension extends AbstractExtension
{
    /**
     * @var ChannelListHelper
     */
    protected $helper;

    public function __construct(ChannelListHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('channelGetChannelLabel', [$this, 'getChannelLabel']),
        ];
    }

    public function getChannelLabel(string $channel): string
    {
        return $this->helper->getChannelLabel($channel);
    }
}
