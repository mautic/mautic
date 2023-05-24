<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\OptionsDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

final class TransmissionDTO implements \JsonSerializable
{
    private ?OptionsDTO $options;

    /**
     * @var RecipientDTO[]
     */
    private array $recipients = [];

    private ?string $campaignId = null;

    private string $returnPath;

    private ContentDTO $content;

    public function __construct(ContentDTO $content, ?string $returnPath, OptionsDTO $options = null)
    {
        $this->content    = $content;
        $this->returnPath = $returnPath;
        $this->options    = $options;
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
