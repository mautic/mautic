<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyLeadRepository.
 */
class CompanyLeadRepository extends CommonRepository
{
    /**
     * Get companies by leadId.
     *
     * @param $leadId
     * @param $companyId
     *
     * @return array
     */
    public function getCompaniesByLeadId($leadId, $companyId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.company_id, comp.companyname, comp.companycity, comp.companycountry')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'comp.id = cl.company_id')
        ->where('cl.lead_id = :leadId')
        ->setParameter('leadId', $leadId);

        $q->andWhere(
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

    public function getCompanyLeads($companyId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $q->where($q->expr()->eq('cl.company_id', ':company'))
            ->setParameter(':company', $companyId);

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param $leadId
     *
     * @return array
     */
    public function getLatestCompanyForLead($leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.company_id, comp.companyname, comp.companycity, comp.companycountry')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'comp.id = cl.company_id')
            ->where('cl.lead_id = :leadId')
            ->setParameter('leadId', $leadId);
        $q->orderBy('cl.date_added', 'DESC');
        $result = $q->execute()->fetchAll();

        return !empty($result) ? $result[0] : [];
    }
}
