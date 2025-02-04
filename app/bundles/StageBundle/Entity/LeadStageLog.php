<?php

namespace Mautic\StageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;

class LeadStageLog
{
    public const TABLE_NAME = 'stage_lead_action_log';

    /**
     * @var Stage
     **/
    private $stage;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     **/
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
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

    /**
     * @return mixed
     */
    public function getDateFired()
    {
        return $this->dateFired;
    }

    /**
     * @param mixed $dateFired
     */
    public function setDateFired($dateFired): void
    {
        $this->dateFired = $dateFired;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param IpAddress $ipAddress
     */
    public function setIpAddress($ipAddress): void
    {
        $this->ipAddress = $ipAddress;
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
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * @param mixed $stage
     */
    public function setStage($stage): void
    {
        $this->stage = $stage;
    }
}
