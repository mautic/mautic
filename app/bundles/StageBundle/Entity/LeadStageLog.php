<?php

namespace Mautic\StageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class LeadStageLog
{
    /**
     * @var Stage
     **/
    private $stage;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     **/
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('stage_lead_action_log')
            ->setCustomRepositoryClass(LeadStageLogRepository::class);

        $builder->createManyToOne('stage', 'Stage')
            ->isPrimaryKey()
            ->addJoinColumn('stage_id', 'id', true, false, 'CASCADE')
            ->inversedBy('log')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addIpAddress(true);

        $builder->createField('dateFired', 'datetime')
            ->columnName('date_fired')
            ->build();
    }

    public function getDateFired()
    {
        return $this->dateFired;
    }

    public function setDateFired($dateFired): void
    {
        $this->dateFired = $dateFired;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress($ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getLead()
    {
        return $this->lead;
    }

    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    public function getStage()
    {
        return $this->stage;
    }

    public function setStage($stage): void
    {
        $this->stage = $stage;
    }
}
