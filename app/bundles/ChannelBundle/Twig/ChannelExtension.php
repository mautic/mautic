<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Twig;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;

class ChannelExtension
{
    public function __construct(
        private readonly DncReasonHelper $dncReasonHelper,
        private readonly ChannelListHelper $channelListHelper,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getChannelDncText')]
    public function getChannelDncText(int $reasonId): string
    {
        try {
            return $this->dncReasonHelper->toText($reasonId);
        } catch (UnknownDncReasonException $e) {
            return $e->getMessage();
        }
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getChannelLabel')]
    public function getChannelLabel(string $channel): string
    {
        return $this->channelListHelper->getChannelLabel($channel);
    }
}
