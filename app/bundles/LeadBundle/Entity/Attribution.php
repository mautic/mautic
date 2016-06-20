<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\StageBundle\Entity\Stage;

/**
 * Class Attribution
 */
class Attribution
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var Stage
     */
    private $stage;

    /**
     * @var string
     */
    private $stageName;

    /**
     * @var string
     */
    private $comments;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var string
     */
    private $action;

    /**
     * @var float
     */
    private $attribution;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var string
     */
    private $campaignName;

    /**
     * @var IpAddress
     */
    private $ipAddress;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_attributions')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\AttributionRepository')
            ->addLifecycleEvent('extractAttribution', 'preUpdate')
            ->addLifecycleEvent('extractAttribution', 'prePersist')
            ->addIndex(['channel', 'date_added'], 'attribution_channel')
            ->addIndex(['channel', 'lead_id', 'date_added'], 'attribution_channel_lead')
            ->addIndex(['channel', 'channel_id', 'date_added'], 'attribution_channel_specific')
            ->addIndex(['channel', 'channel_id', 'lead_id', 'date_added'], 'attribution_channel_specific_lead')
            ->addIndex(['channel', 'channel_id', 'action'], 'attribution_channel_action_specific')
            ->addIndex(['channel', 'channel_id', 'action', 'lead_id', 'date_added'], 'attribution_channel_action_specific_lead')
            ->addIndex(['campaign_id', 'date_added'], 'attribution_campaign')
            ->addIndex(['campaign_id', 'lead_id', 'date_added'], 'attribution_campaign_lead')
            ->addIndex(['stage_id', 'date_added'], 'attribution_stage')
            ->addIndex(['stage_id', 'lead_id', 'date_added'], 'attribution_stage_lead')
            ->addIndex(['stage_id', 'campaign_id', 'date_added'], 'attribution_stage_campaign')
            ->addIndex(['stage_id', 'campaign_id', 'lead_id', 'date_added'], 'attribution_stage_campaign_lead')
            ->addIndex(['stage_id', 'channel', 'date_added'], 'attribution_stage_channel')
            ->addIndex(['stage_id', 'channel', 'lead_id', 'date_added'], 'attribution_stage_channel_lead')
            ->addIndex(['lead_id', 'date_added'], 'attribution_lead')
            ->addIndex(['date_added'], 'attribution_date_added')
            ->addIndex(['date_added', 'lead_id'], 'attribution_date_added_lead');

        $builder->addId();
        $builder->addLead();
        $builder->addIpAddress(true);
        $builder->addDateAdded();

        $builder->createField('channel', 'string')
            ->build();
        $builder->addNamedField('channelId', 'integer', 'channel_id', true);
        $builder->createField('action', 'string')
            ->build();

        $builder->createManyToOne('stage', 'Mautic\StageBundle\Entity\Stage')
            ->addJoinColumn('stage_id', 'id', true, false, 'SET NULL')
            ->build();
        $builder->addNamedField('stageName', 'text', 'stage_name', true);

        $builder->createField('comments', 'text')
            ->nullable()
            ->build();

        $builder->createManyToOne('campaign', 'Mautic\CampaignBundle\Entity\Campaign')
            ->addJoinColumn('campaign_id', 'id', true, false, 'SET NULL')
            ->build();
        $builder->addNamedField('campaignName', 'text', 'campaign_name', true);

        $builder->createField('attribution', 'float')
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     *
     * @return Attribution
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return Attribution
     */
    public function setDateAdded(\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return Stage
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * @param Stage $stage
     *
     * @return Attribution
     */
    public function setStage(Stage $stage = null)
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return string
     */
    public function getStageName()
    {
        return $this->stageName;
    }

    /**
     * @param string $stageName
     *
     * @return Attribution
     */
    public function setStageName($stageName)
    {
        $this->stageName = $stageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     *
     * @return Attribution
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return Attribution
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param int $channelId
     *
     * @return Attribution
     */
    public function setChannelId($channelId)
    {
        $this->channelId = (int) $channelId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return Attribution
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return float
     */
    public function getAttribution()
    {
        return $this->attribution;
    }

    /**
     * @param float $attribution
     *
     * @return Attribution
     */
    public function setAttribution($attribution)
    {
        $this->attribution = (float) $attribution;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     *
     * @return Attribution
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * @param string $campaignName
     *
     * @return Attribution
     */
    public function setCampaignName($campaignName)
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param IpAddress $ipAddress
     *
     * @return Attribution
     */
    public function setIpAddress(IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }


    /**
     * Get the attribution amount from the lead
     */
    public function extractAttribution()
    {
        if (null == $this->attribution) {
            $this->attribution = (float) $this->lead->getAttribution();
        }

        if (null == $this->dateAdded) {
            $this->dateAdded = new \DateTime;
        }

        if ($this->campaign && empty($this->campaignName)) {
            $this->campaignName = $this->campaign->getName();
        }

        if (!$this->stage) {
            $this->stage = $this->lead->getStage();
        }

        if ($this->stage && empty($this->stageName)) {
            $this->stageName = $this->stage->getName();
        }
    }
}