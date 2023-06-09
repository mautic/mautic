<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

final class OptionsDTO implements \JsonSerializable
{
    private ?string $startTime = null;

    private ?bool $openTracking = null;

    private ?bool $clickTracking = null;

    /** @return array<string, string|bool> */
    public function jsonSerialize(): array
    {
        $json = [];
        if (null !== $this->startTime) {
            $json['start_time'] = $this->startTime;
        }
        if (null !== $this->openTracking) {
            $json['open_tracking'] = $this->openTracking;
        }
        if (null !== $this->clickTracking) {
            $json['click_tracking'] = $this->clickTracking;
        }

        return $json;
    }

    public function setStartTime(?string $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function setOpenTracking(?bool $openTracking): self
    {
        $this->openTracking = $openTracking;

        return $this;
    }

    public function setClickTracking(?bool $clickTracking): self
    {
        $this->clickTracking = $clickTracking;

        return $this;
    }
}
