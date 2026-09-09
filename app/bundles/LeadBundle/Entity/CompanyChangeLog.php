<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyChangeLogRepository::class)]
#[ORM\Table(name: 'lead_companies_change_log')]
#[ORM\Index(columns: ['date_added'], name: 'company_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class CompanyChangeLog
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'companyChangeLog')]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text', length: 50)]
    private $type;

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
     * @var int
     */
    #[ORM\Column(name: 'company_id', type: 'integer')]
    private $company;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * Set delta.
     *
     * @param int $company
     */
    public function setCompany($company): static
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return int
     */
    public function getCompany()
    {
        return $this->company;
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
}
