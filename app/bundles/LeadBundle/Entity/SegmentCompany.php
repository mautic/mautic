<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class SegmentCompany
{
    public const TABLE_NAME     = 'company_segments_companies';
    public const RELATIONS_NAME = 'css';

    private CompanySegment $companySegment;

    private Company $company;

    private \DateTimeInterface $dateAdded;

    private bool $manuallyRemoved = false;

    private bool $manuallyAdded = false;

    public static function loadMetadata(ORMClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SegmentCompanyRepository::class);

        $builder->createManyToOne('companySegment', CompanySegment::class)
            ->isPrimaryKey()
            ->inversedBy('segmentCompanies')
            ->addJoinColumn('segment_id', 'id', false, false, 'CASCADE')
            ->cascadeRefresh()
            ->build();

        $builder->createManyToOne('company', Company::class)
            ->makePrimaryKey()
            ->addJoinColumn('company_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addDateAdded();

        $builder->createField('manuallyRemoved', 'boolean')
            ->columnName('manually_removed')
            ->build();

        $builder->createField('manuallyAdded', 'boolean')
            ->columnName('manually_added')
            ->build();

        $builder->addIndex(['manually_removed'], 'companies_segment_manually_removed');
    }

    public function getCompanySegment(): CompanySegment
    {
        return $this->companySegment;
    }

    public function setCompanySegment(CompanySegment $companySegment): void
    {
        $this->companySegment = $companySegment;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function getDateAdded(): \DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function setDateAdded(\DateTimeInterface $dateAdded): void
    {
        $this->dateAdded = $dateAdded;
    }

    public function isManuallyRemoved(): bool
    {
        return $this->manuallyRemoved;
    }

    public function setManuallyRemoved(bool $manuallyRemoved): void
    {
        $this->manuallyRemoved = $manuallyRemoved;

        if ($manuallyRemoved) {
            $this->manuallyAdded = false;
        }
    }

    public function isManuallyAdded(): bool
    {
        return $this->manuallyAdded;
    }

    public function setManuallyAdded(bool $manuallyAdded): void
    {
        $this->manuallyAdded = $manuallyAdded;

        if ($manuallyAdded) {
            $this->manuallyRemoved = false;
        }
    }
}
