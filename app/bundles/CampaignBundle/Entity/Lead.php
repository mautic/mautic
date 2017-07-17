<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Lead.
 */
class Lead
{
    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTime
     **/
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastExited;

    /**
     * @var bool
     */
    private $manuallyRemoved = false;

    /**
     * @var bool
     */
    private $manuallyAdded = false;

    /**
     * @var int
     */
    private $rotation = 1;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_leads')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\LeadRepository')
            ->addIndex(['date_added'], 'campaign_leads_date_added')
            ->addIndex(['date_last_exited'], 'campaign_leads_date_exited')
            ->addIndex(['campaign_id', 'manually_removed', 'lead_id', 'rotation'], 'campaign_leads');

        $builder->createManyToOne('campaign', 'Campaign')
            ->isPrimaryKey()
            ->inversedBy('leads')
            ->addJoinColumn('campaign_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();

        $builder->createField('manuallyRemoved', 'boolean')
            ->columnName('manually_removed')
            ->build();

        $builder->createField('manuallyAdded', 'boolean')
            ->columnName('manually_added')
            ->build();

        $builder->addNamedField('dateLastExited', 'datetime', 'date_last_exited', true);

        $builder->addField('rotation', 'integer');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('campaignLead')
                 ->addListProperties(
                     [
                         'dateAdded',
                         'manuallyRemoved',
                         'manuallyAdded',
                         'rotation',
                         'dateLastExited',
                     ]
                 )
                ->addProperties(
                    [
                        'lead',
                        'campaign',
                    ]
                )
                 ->build();
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date)
    {
        $this->dateAdded = $date;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
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
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved($manuallyRemoved)
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded($manuallyAdded)
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     *
     * @return Lead
     */
    public function setRotation($rotation)
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateLastExited()
    {
        return $this->dateLastExited;
    }

    /**
     * @param \DateTime $dateLastExited
     *
     * @return Lead
     */
    public function setDateLastExited(\DateTime $dateLastExited)
    {
        $this->dateLastExited = $dateLastExited;

        return $this;
    }
}
