<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;

readonly class CompanyDeduper
{
    use DeduperTrait;

    public function __construct(
        FieldList $fieldList,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        private CompanyRepository $companyRepository,
    ) {
        $this->fieldList                  = $fieldList;
        $this->fieldsWithUniqueIdentifier = $fieldsWithUniqueIdentifier;
        $this->object                     = 'company';
    }

    /**
     * @return Company[]
     *
     * @throws UniqueFieldNotFoundException
     */
    public function checkForDuplicateCompanies(array $queryFields): array
    {
        $uniqueData = $this->getUniqueData($queryFields);
        if ([] === $uniqueData) {
            throw new UniqueFieldNotFoundException();
        }

        return $this->companyRepository->getCompaniesByUniqueFields($uniqueData);
    }
}
