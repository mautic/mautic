<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\OptionsDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

final class TransmissionDTO implements \JsonSerializable
{
    /**
     * @var RecipientDTO[]
     */
    private array $recipients = [];

    private ?string $campaignId = null;

    public function __construct(private ContentDTO $content, private string $returnPath, private ?OptionsDTO $options = null)
    {
    }

    public function addRecipient(RecipientDTO $recipientDTO): self
    {
        $this->recipients[] = $recipientDTO;

        return $this;
    }

    public function setCampaignId(?string $campaignId): self
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $json = [
            'return_path' => $this->returnPath,
            'recipients'  => $this->recipients,
            'content'     => $this->content,
        ];
        if (null !== $this->options) {
            $json['options'] = $this->options;
        }
        if (!empty($this->campaignId)) {
            $json['campaign_id'] = $this->campaignId;
        }

        return $json;
    }
}
