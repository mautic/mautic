<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class CompanyLead
{
    public const TABLE_NAME = 'companies_leads';

    /**
     * @var Company
     **/
    private $company;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var bool|null
     */
    private $primary = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CompanyLeadRepository::class);

        $builder->createManyToOne('company', 'Company')
            ->makePrimaryKey()
            ->addJoinColumn('company_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();

        $builder->createField('primary', 'boolean')
            ->columnName('is_primary')
            ->nullable()
            ->build();
    }

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
