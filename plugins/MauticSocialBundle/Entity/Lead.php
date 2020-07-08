<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * @ORM\Entity
 * @ORM\Table(name="monitoring_leads")
 * @ORM\Entity(repositoryClass="MauticPlugin\MauticSocialBundle\Entity\LeadRepository")
 */
class Lead
{
    /**
     * @var Monitoring
     */
    private $monitor;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('monitoring_leads')
            ->setCustomRepositoryClass('MauticPlugin\MauticSocialBundle\Entity\LeadRepository');

        $builder->createManyToOne('monitor', 'Monitoring')
            ->isPrimaryKey()
            ->addJoinColumn('monitor_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addNamedField('dateAdded', 'datetime', 'date_added');
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param $dateAdded
     *
     * @return $this
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param $lead
     *
     * @return $this
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMonitor()
    {
        return $this->monitor;
    }

    /**
     * @param $monitor
     *
     * @return $this
     */
    public function setMonitor($monitor)
    {
        $this->monitor = $monitor;

        return $this;
    }
}
