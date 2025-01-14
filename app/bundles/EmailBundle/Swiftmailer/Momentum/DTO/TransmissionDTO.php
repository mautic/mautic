<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\OptionsDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

/**
 * Class Mail.
 */
class TransmissionDTO implements \JsonSerializable
{
    /**
     * @var OptionsDTO|null
     */
    private $options;

    /**
     * @var RecipientDTO[]
     */
    private $recipients = [];

    /**
     * @var string|null
     */
    private $campaignId;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $returnPath;

    /**
     * @var ContentDTO
     */
    private $content;

    /**
     * TransmissionDTO constructor.
     *
     * @param string $returnPath
     */
    public function __construct(ContentDTO $content, $returnPath, OptionsDTO $options = null)
    {
        $this->content    = $content;
        $this->returnPath = $returnPath;
        $this->options    = $options;
    }

    /**
     * @return TransmissionDTO
     */
    public function addRecipient(RecipientDTO $recipientDTO)
    {
        $this->recipients[] = $recipientDTO;

        return $this;
    }

    /**
     * @param $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
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
        if (!empty($this->description)) {
            $json['description'] = $this->description;
        }

        return $json;
    }
}
