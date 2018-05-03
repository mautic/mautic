<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\OptionsDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientsDTO;

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
     * @var RecipientsDTO
     */
    private $recipients;

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
     * @param RecipientsDTO   $recipients
     * @param ContentDTO      $content
     * @param OptionsDTO|null $options
     */
    public function __construct(RecipientsDTO $recipients, ContentDTO $content, OptionsDTO $options = null)
    {
        $this->recipients = $recipients;
        $this->content    = $content;
        $this->options    = $options;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $json = [
            'return_path' => $this->returnPath,
            'recipients'  => json_encode($this->recipients),
            'content'     => json_encode($this->content),
        ];
        if ($this->options !== null) {
            $json['options'] = json_encode($this->options);
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
