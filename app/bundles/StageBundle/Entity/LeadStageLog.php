<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;

#[ORM\Entity(repositoryClass: LeadStageLogRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadStageLog
{
    public const TABLE_NAME = 'stage_lead_action_log';

    /**
     * @var Stage
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Stage::class, inversedBy: 'log')]
    #[ORM\JoinColumn(name: 'stage_id', onDelete: 'CASCADE')]
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
     */
    #[ORM\Column(name: 'date_fired', type: 'datetime')]
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addLead(false, 'CASCADE', true);

        $builder->addIpAddress(true);
    }

    /**
     * @return \DateTimeInterface|null
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
     * @return \Mautic\LeadBundle\Entity\Lead|null
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
     * @return Stage|null
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
