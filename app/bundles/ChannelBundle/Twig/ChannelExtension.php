<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Twig;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class ChannelExtension
{
    public function __construct(
        private DncReasonHelper $dncReasonHelper,
        private ChannelListHelper $channelListHelper,
    ) {
    }

    #[AsTwigFunction(name: 'getChannelDncText')]
    public function getChannelDncText(int $reasonId): string
    {
        try {
            return $this->dncReasonHelper->toText($reasonId);
        } catch (UnknownDncReasonException $e) {
            return $e->getMessage();
        }
    }

    #[AsTwigFunction(name: 'getChannelLabel')]
    public function getChannelLabel(string $channel): string
    {
        return $this->channelListHelper->getChannelLabel($channel);
    }
}
