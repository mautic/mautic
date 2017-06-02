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
}
