<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyLeadRepository
 *
 * @package Mautic\LeadBundle\Entity
 */
class CompanyLeadRepository extends CommonRepository
{

    /**
     * Get companies by leadId
     *
     * @param $leadId
     * @param $companyId
     * @return array
     */
    public function getCompaniesByLeadId($leadId, $companyId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
        ->where('cl.lead_id = :leadId')
        ->setParameter('leadId',$leadId);

        $q->where(
            $q->expr()->eq('cl.manually_removed', ':false')
        )->setParameter('false', false, 'boolean');

        if ($companyId) {
            $q->where(
                $q->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('companyId', $companyId);
        }

        $result = $q->execute()->fetchAll();

        return $result;
    }
}