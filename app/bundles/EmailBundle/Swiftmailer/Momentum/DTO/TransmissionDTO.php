<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        if ($this->options !== null) {
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
