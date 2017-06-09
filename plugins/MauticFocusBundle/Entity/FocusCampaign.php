<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class FocusCampaign.
 */
class FocusCampaign
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    private $focus;

    /**
     * @var \Mautic\CampaignBundle\Entity\Campaign
     */
    private $campaign;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CampaignBundle\Entity\LeadEventLog
     */
    private $leadeventlog;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('focus_campaign')
            ->setCustomRepositoryClass('MauticPlugin\MauticFocusBundle\Entity\FocusCampaignRepository');

        $builder->addId();

        $builder->createManyToOne('campaign', 'Mautic\CampaignBundle\Entity\Campaign')
            ->addJoinColumn('campaign_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('focus', 'Focus')
            ->addJoinColumn('focus_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('lead', 'Mautic\LeadBundle\Entity\Lead')
            ->addJoinColumn('lead_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('leadeventlog', 'Mautic\CampaignBundle\Entity\LeadEventLog')
            ->addJoinColumn('leadeventlog_id', 'id', true, false, 'CASCADE')
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('focusCampaign')
            ->addProperties(
                [
                    'id',
                    'focus',
                    'campaign',
                    'leadeventlog',
                ]
            )
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Focus
     */
    public function getFocus()
    {
        return $this->focus;
    }

    /**
     * @param Focus
     */
    public function setFocus(Focus $focus)
    {
        $this->focus = $focus;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return LeadEventLog
     */
    public function getLeadEventLog()
    {
        return $this->leadeventlog;
    }

    /**
     * @param LeadEventLog
     */
    public function setLeadEventLog(LeadEventLog $leadeventlog)
    {
        $this->leadeventlog = $leadeventlog;
    }
}
