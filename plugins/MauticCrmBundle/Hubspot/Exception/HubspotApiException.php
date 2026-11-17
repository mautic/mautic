<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Exception;

final class HubspotApiException extends \RuntimeException
{
    public function isRateLimited(): bool
    {
        return 429 === $this->getCode();
    }

    public function isTemporary(): bool
    {
        return $this->isRateLimited() || $this->getCode() >= 500;
    }
}
