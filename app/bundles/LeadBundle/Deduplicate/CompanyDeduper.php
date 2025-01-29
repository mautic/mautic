<?php

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\FieldModel;

class CompanyDeduper
{
    use DeduperTrait;

    public function __construct(
        FieldModel $fieldModel,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        private CompanyRepository $companyRepository,
    ) {
        $this->fieldModel                 = $fieldModel;
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
        if (empty($uniqueData)) {
            throw new UniqueFieldNotFoundException();
        }

        return $this->companyRepository->getCompaniesByUniqueFields($uniqueData);
    }
}
