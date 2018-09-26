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
     * @param CompanyLead[] $entities
     */
    public function saveEntities($entities, $new = true)
    {
        // Get a list of contacts and set primary to 0
        if ($new) {
            $contacts = [];
            foreach ($entities as $entity) {
                $contactId            = $entity->getLead()->getId();
                $contacts[$contactId] = $contactId;
                $entity->setPrimary(true);
            }
            if ($contactId) {
                $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
                    ->update(MAUTIC_TABLE_PREFIX.'companies_leads')
                    ->set('is_primary', 0);

                $qb->where(
                    $qb->expr()->in('lead_id', $contacts)
                )->execute();
            }
        }

        return parent::saveEntities($entities);
    }

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

        $q->select('cl.company_id, cl.date_added as date_associated, cl.is_primary, comp.*')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'comp.id = cl.company_id')
        ->where('cl.lead_id = :leadId')
        ->setParameter('leadId', $leadId);

        $q->andWhere(
            $q->expr()->eq('cl.manually_removed', ':false')
        )->setParameter('false', false, 'boolean');

        if ($companyId) {
            $q->andWhere(
                $q->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('companyId', $companyId);
        }

        $result = $q->execute()->fetchAll();

        return $result;
    }

    /**
     * @param $companyId
     *
     * @return array
     */
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

    /**
     * @param $leadId
     * @param $companyId
     */
    public function getCompanyLeadEntity($leadId, $companyId)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cl.is_primary, cl.lead_id, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->where(
                    $qb->expr()->eq('cl.manually_removed', 0),
                    $qb->expr()->eq('cl.lead_id', ':leadId'),
                    $qb->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('leadId', $leadId)
            ->setParameter('companyId', $companyId);

        $companies = $qb->execute()->fetchAll();

        return $companies;
    }

    /**
     * @param Lead $lead
     *
     * @return mixed
     */
    public function getEntitiesByLead(Lead $lead)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cl')
            ->from('MauticLeadBundle:CompanyLead', 'cl')
            ->where(
                $qb->expr()->eq('cl.manuallyRemoved', 0),
                $qb->expr()->eq('cl.lead', ':lead')
            )->setParameter('lead', $lead);

        $companies = $qb->getQuery()->execute();

        return $companies;
    }

    /**
     * Updates leads company name If company name changed and company is primary.
     *
     * @param Company $company
     */
    public function updateLeadsPrimaryCompanyName(Company $company)
    {
        if ($company->isNew() || empty($company->getChanges()['fields']['companyname'])) {
            return;
        }
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');
        $q->where($q->expr()->eq('cl.company_id', ':companyId'))
            ->setParameter(':companyId', $company->getId())
            ->andWhere('cl.is_primary = 1');
        $leadIds = $q->execute()->fetchColumn();
        if (!empty($leadIds)) {
            $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'leads')
            ->set('company', ':company')
            ->setParameter(':company', $company->getName())
            ->where(
                $q->expr()->in('id', $leadIds)
            )->execute();
        }
    }
}
