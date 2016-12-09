<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\StageBundle\Entity\Stage;

/**
 * Class StagesChangeLog.
 */
class StagesChangeLog
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
     * @var Stage
     */
    private $stage;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @var string
     */
    private $actionName;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_stages_change_log')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\StagesChangeLogRepository');

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'stageChangeLog');

        $builder->createField('eventName', 'string')
            ->columnName('event_name')
            ->build();

        $builder->createField('actionName', 'string')
            ->columnName('action_name')
            ->build();

        $builder->createManyToOne('stage', 'Mautic\StageBundle\Entity\Stage')
            ->inversedBy('log')
            ->addJoinColumn('stage_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addDateAdded();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set eventName.
     *
     * @param string $eventName
     *
     * @return StagesChangeLog
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Get eventName.
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Set actionName.
     *
     * @param string $actionName
     *
     * @return StagesChangeLog
     */
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Get actionName.
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     *
     * @return StagesChangeLog
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set lead.
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     *
     * @return StagesChangeLog
     */
    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead.
     *
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set stage.
     *
     * @param \Mautic\StageBundle\Entity\Stage $stage
     *
     * @return StagesChangeLog
     */
    public function setStage(\Mautic\StageBundle\Entity\Stage $stage)
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * Get stage.
     *
     * @return \Mautic\StageBundle\Entity\Stage
     */
    public function getStage()
    {
        return $this->stage;
    }
}
