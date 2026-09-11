<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: CompanyChangeLogRepository::class)]
#[ORM\Table(name: 'lead_companies_change_log')]
#[ORM\Index(columns: ['date_added'], name: 'company_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class CompanyChangeLog
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
    private $dateAdded;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'companyChangeLog');

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
