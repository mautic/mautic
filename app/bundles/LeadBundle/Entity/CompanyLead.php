<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyLeadRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class CompanyLead
{
    public const TABLE_NAME = 'companies_leads';

    /**
     * @var Company
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', nullable: false, onDelete: 'CASCADE')]
    private $company;

    /**
     * @var Lead
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'is_primary', type: 'boolean', nullable: true)]
    private $primary = false;

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date): void
    {
        $this->dateAdded = $date;
    }

    /**
     * @return Lead
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
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return Company
     */
    public function getCompanies()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany($company): void
    {
        $this->company = $company;
    }

    /**
     * @param bool $primary
     */
    public function setPrimary($primary): void
    {
        $this->primary = $primary;
    }

    /**
     * @return bool|null
     */
    public function getPrimary()
    {
        return $this->primary;
    }
}
