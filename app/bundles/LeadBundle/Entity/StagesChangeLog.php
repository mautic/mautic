<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\StageBundle\Entity\Stage;

#[ORM\Entity(repositoryClass: StagesChangeLogRepository::class)]
#[ORM\Table(name: 'lead_stages_change_log')]
#[ORM\Index(columns: ['date_added'], name: 'lead_stages_change_log_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
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
     * @var Stage|null
     */
    #[ORM\ManyToOne(targetEntity: Stage::class, inversedBy: 'log')]
    #[ORM\JoinColumn(name: 'stage_id', onDelete: 'CASCADE')]
    private $stage;

    /**
     * @var string
     */
    #[ORM\Column(name: 'event_name', type: 'string', length: 191)]
    private $eventName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'action_name', type: 'string', length: 191)]
    private $actionName;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'stageChangeLog');

        $builder->addDateAdded();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    public function setStage(Stage $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * @return Stage|null
     */
    public function getStage()
    {
        return $this->stage;
    }
}
