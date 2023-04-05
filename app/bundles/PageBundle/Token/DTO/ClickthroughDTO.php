<?php

namespace Mautic\PageBundle\Token\DTO;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\ClickthroughHelper;

class ClickthroughDTO
{
    private ?string $channel;

    private ?string $stat;

    public function __construct(string $clickthrough)
    {
        try {
            $clickthrough      = ClickthroughHelper::decodeArrayFromUrl($clickthrough);
            $channel           = $clickthrough['channel'] ?? [];
            $this->channel     = key((is_array($channel) ? $channel : [$channel]));
            $this->stat        = $clickthrough['stat'] ?? null;
        } catch (InvalidDecodedStringException $invalidDecodedStringException) {
        }
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function getStat(): ?string
    {
        return $this->stat;
    }
}
