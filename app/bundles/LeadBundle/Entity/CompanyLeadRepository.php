<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<CompanyLead>
 */
class CompanyLeadRepository extends CommonRepository
{
    public const DELETE_BATCH_SIZE = 1000;

    /**
     * @param CompanyLead[] $entities
     */
    public function saveEntities($entities, $new = true)
    {
        // Get a list of contacts and set primary to 0
        if ($new) {
            $contacts  = [];
            $contactId = null;
            foreach ($entities as $entity) {
                $contactId = $entity->getLead()->getId();
                if (!isset($contacts[$contactId])) {
                    // Set one company from the batch as as primary
                    $entity->setPrimary(true);
                }

                $contacts[$contactId] = $contactId;
            }

            if ($contactId) {
                // Only one company should be set as primary so reset all in order to let the entity update the one
                $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
                    ->update(MAUTIC_TABLE_PREFIX.'companies_leads')
                    ->set('is_primary', 0);

                $qb->where(
                    $qb->expr()->in('lead_id', $contacts)
                )->executeQuery();
            }
        }

        return parent::saveEntities($entities);
    }

    /**
     * Get companies by leadId.
     *
     * @return array
     */
    public function getCompaniesByLeadId($leadId, $companyId = null, ?bool $onlyPrimary = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.company_id, cl.date_added as date_associated, cl.is_primary, comp.*')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'companies', 'comp', 'comp.id = cl.company_id')
        ->where('cl.lead_id = :leadId')
        ->setParameter('leadId', $leadId);

        if ($companyId) {
            $q->andWhere(
                $q->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('companyId', $companyId);
        }

        if ($onlyPrimary) {
            $q->andWhere(
                $q->expr()->eq('cl.is_primary', true)
            );
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array
     */
    public function getCompanyLeads($companyId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $q->where($q->expr()->eq('cl.company_id', ':company'))
            ->setParameter('company', $companyId);

        return $q->execute()->fetchAllAssociative();
    }

    /**
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

        $result = $q->execute()->fetchAllAssociative();

        return !empty($result) ? $result[0] : [];
    }

    public function getCompanyLeadEntity($leadId, $companyId)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cl.is_primary, cl.lead_id, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->where(
                $qb->expr()->eq('cl.lead_id', ':leadId'),
                $qb->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('leadId', $leadId)
            ->setParameter('companyId', $companyId);

        return $qb->execute()->fetchAllAssociative();
    }

    /**
     * @return mixed
     */
    public function getEntitiesByLead(Lead $lead)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cl')
            ->from(CompanyLead::class, 'cl')
            ->where(
                $qb->expr()->eq('cl.lead', ':lead')
            )->setParameter('lead', $lead);

        return $qb->getQuery()->execute();
    }

    /**
     * Updates leads company name If company name changed and company is primary.
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
            ->setParameter('companyId', $company->getId())
            ->andWhere('cl.is_primary = 1');
        $leadIds = $q->execute()->fetchOne();
        if (!empty($leadIds)) {
            $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'leads')
            ->set('company', ':company')
            ->setParameter('company', $company->getName())
            ->where(
                $q->expr()->in('id', $leadIds)
            )->execute();
        }
    }

    public function removeContactPrimaryCompany(int $leadId): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads');
        $qb->where(
            $qb->expr()->eq('lead_id', $leadId)
        )->andWhere(
            $qb->expr()->eq('is_primary', 1)
        )->executeStatement();
    }

    public function removeAllSecondaryCompanies(): void
    {
        $table_name    = MAUTIC_TABLE_PREFIX.'companies_leads';
        $sql           = "DELETE FROM {$table_name} WHERE is_primary = 0 LIMIT ".self::DELETE_BATCH_SIZE;
        $conn          = $this->getEntityManager()->getConnection();
        while ($conn->executeQuery($sql)->rowCount()) {
        }
    }

    public function removeContactSecondaryCompanies(int $leadId): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads');
        $qb->where(
            $qb->expr()->eq('lead_id', $leadId)
        )->andWhere(
            $qb->expr()->eq('is_primary', 0)
        )->executeStatement();
    }
}
