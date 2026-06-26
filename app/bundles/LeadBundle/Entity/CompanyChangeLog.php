<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

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
    private $company;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_companies_change_log')
            ->setCustomRepositoryClass(CompanyChangeLogRepository::class)
            ->addIndex(['date_added'], 'company_date_added');

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'companyChangeLog');

        $builder->createField('type', 'text')
            ->length(50)
            ->build();

        $builder->createField('eventName', 'string')
            ->columnName('event_name')
            ->build();

        $builder->createField('actionName', 'string')
            ->columnName('action_name')
            ->build();

        $builder->createField('company', 'integer')
            ->columnName('company_id')
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
     * Set type.
     *
     * @param string $type
     */
    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set eventName.
     *
     * @param string $eventName
     */
    public function setEventName($eventName): static
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
     */
    public function setActionName($actionName): static
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
     * Get company.
     *
     * @return int
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set lead.
     */
    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
