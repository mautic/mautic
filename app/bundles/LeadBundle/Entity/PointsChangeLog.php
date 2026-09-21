<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\PointBundle\Entity\Group;

class PointsChangeLog
{
    public const TABLE_NAME = 'lead_points_change_log';

    /**
     * @var string
     */
    private $id;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var IpAddress|null
     */
    private $ipAddress;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @var string
     */
    private $actionName;

    /**
     * @var int
     */
    private $delta;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    private ?Group $group = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(PointsChangeLogRepository::class)
            ->addIndex(['date_added'], 'point_date_added');

        $builder->addBigIntIdField();

        $builder->addLead(false, 'CASCADE', false, 'pointsChangeLog');

        $builder->addIpAddress(true);

        $builder->createField('type', 'text')
            ->length(50)
            ->build();

        $builder->createField('eventName', 'string')
            ->columnName('event_name')
            ->build();

        $builder->createField('actionName', 'string')
            ->columnName('action_name')
            ->build();

        $builder->addField('delta', 'integer');

        $builder->createManyToOne('group', Group::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->addDateAdded();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param string $type
     */
    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $eventName
     */
    public function setEventName($eventName): static
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @param string $actionName
     */
    public function setActionName($actionName): static
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * @param int $delta
     */
    public function setDelta($delta): static
    {
        $this->delta = $delta;

        return $this;
    }

    /**
     * @return int
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setIpAddress(IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;
    }
}
