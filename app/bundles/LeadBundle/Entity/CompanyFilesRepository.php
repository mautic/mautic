<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyFilesRepository.
 */
class CompanyFilesRepository extends CommonRepository
{
    /**
     * @param $companyId
     *
     * @return array
     */
    public function getCompanyFiles($companyId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('cf.id, cf.title, cf.path')
            ->from(MAUTIC_TABLE_PREFIX.'company_files', 'cf');

        $q->where($q->expr()->eq('cf.company_id', ':company'))
            ->setParameter(':company', $companyId);

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
