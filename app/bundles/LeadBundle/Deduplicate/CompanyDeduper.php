<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\UniqueFieldNotFoundException;
use Mautic\LeadBundle\Model\FieldModel;

class CompanyDeduper
{
    use DeduperTrait;

    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    /**
     * DedupModel constructor.
     */
    public function __construct(FieldModel $fieldModel, CompanyRepository $companyRepository)
    {
        $this->fieldModel        = $fieldModel;
        $this->companyRepository = $companyRepository;
        $this->object            = 'company';
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
