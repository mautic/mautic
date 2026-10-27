<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DTO;

final readonly class EventPreviewStatDto
{
    public function __construct(
        public mixed $value,
        public ?string $tooltip = null,
    ) {
    }
}
