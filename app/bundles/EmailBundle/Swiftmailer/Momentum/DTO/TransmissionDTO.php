<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\OptionsDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

/**
 * Class Mail.
 */
final class TransmissionDTO implements \JsonSerializable
{
    /**
     * @var OptionsDTO|null
     */
    private $options = null;

    /**
     * @var RecipientDTO[]
     */
    private $recipients = [];

    /**
     * @var string|null
     */
    private $campaignId = null;

    /**
     * @var string|null
     */
    private $description = null;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $substitutionData = [];

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
     * @param ContentDTO      $content
     * @param string          $returnPath
     * @param OptionsDTO|null $options
     */
    public function __construct(ContentDTO $content, $returnPath, OptionsDTO $options = null)
    {
        $this->content    = $content;
        $this->returnPath = $returnPath;
        $this->options    = $options;
    }

    /**
     * @param RecipientDTO $recipientDTO
     *
     * @return TransmissionDTO
     */
    public function addRecipient(RecipientDTO $recipientDTO)
    {
        $this->recipients[] = $recipientDTO;

        return $this;
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
        if ($this->options !== null) {
            $json['options'] = $this->options;
        }
        if ($this->campaignId !== null) {
            $json['campaign_id'] = $this->campaignId;
        }
        if ($this->description !== null) {
            $json['description'] = $this->description;
        }
        if (count($this->metadata) !== 0) {
            $json['metadata'] = $this->metadata;
        }
        if (count($this->substitutionData) !== 0) {
            $json['substitution_data'] = $this->substitutionData;
        }

        return $json;
    }
}
