<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: 'focus_stats')]
#[ORM\Index(columns: ['type'], name: 'focus_type')]
#[ORM\Index(columns: ['type', 'type_id'], name: 'focus_type_id')]
#[ORM\Index(columns: ['date_added'], name: 'focus_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stat
{
    // Used for querying stats
    public const TYPE_FORM         = 'submission';

    public const TYPE_CLICK        = 'click';

    public const TYPE_NOTIFICATION = 'view';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var Focus
     */
    #[ORM\ManyToOne(targetEntity: 'Focus')]
    #[ORM\JoinColumn(name: 'focus_id', nullable: false, onDelete: 'CASCADE')]
    private $focus;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $type;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'type_id', type: 'integer', nullable: true)]
    private $typeId;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFocus()
    {
        return $this->focus;
    }

    /**
     * @param mixed $focus
     */
    public function setFocus($focus): static
    {
        $this->focus = $focus;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param mixed $typeId
     */
    public function setTypeId($typeId): static
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }
}
