<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: LeadPointLogRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadPointLog
{
    public const string TABLE_NAME = 'point_lead_action_log';

    /**
     * @var Point
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Point::class, inversedBy: 'log')]
    #[ORM\JoinColumn(name: 'point_id', onDelete: 'CASCADE')]
    private $point;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     */
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addIpAddress(true);

        $builder->createField('dateFired', 'datetime')
            ->columnName('date_fired')
            ->build();
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
     * @return Point|null
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param mixed $point
     */
    public function setPoint($point): void
    {
        $this->point = $point;
    }
}
