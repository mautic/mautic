<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;

/**
 * @extends CommonRepository<CompanyLead>
 */
class CompanyLeadRepository extends CommonRepository
{
    public const DELETE_BATCH_SIZE = 1000;

    /**
     * @param CompanyLead[] $entities
     */
    public function saveEntities($entities, $new = true): void
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
                )->executeStatement();
            }
        }

        parent::saveEntities($entities);
    }

    /**
     * Get companies by leadId.
     */
    public function getCompaniesByLeadId($leadId, $companyId = null, ?bool $onlyPrimary = null): array
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
     * @return mixed[]
     *
     * @throws PrimaryCompanyNotFoundException
     */
    public function getPrimaryCompanyByLeadId(int $leadId): array
    {
        $companies = $this->getCompaniesByLeadId($leadId);
        foreach ($companies as $company) {
            if ($company['is_primary']) {
                return $company;
            }
        }

        throw new PrimaryCompanyNotFoundException();
    }

    /**
     * @return string[]
     */
    public function getCompanyIdsByLeadId(string $leadId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->where('cl.lead_id = :leadId')
            ->setParameter('leadId', $leadId);

        return array_map(
            fn (array $company) => (string) $company['company_id'],
            $q->executeQuery()->fetchAllAssociative()
        );
    }

    /**
     * @param int $companyId
     */
    public function getCompanyLeads($companyId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $q->where($q->expr()->eq('cl.company_id', ':company'))
            ->setParameter('company', $companyId);

        return $q->executeQuery()->fetchAllAssociative();
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

        $result = $q->executeQuery()->fetchAllAssociative();

        return !empty($result) ? $result[0] : [];
    }

    /**
     * @return mixed[]
     */
    public function getCompanyLeadEntity($leadId, $companyId): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('cl.is_primary, cl.lead_id, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->where(
                $qb->expr()->eq('cl.lead_id', ':leadId'),
                $qb->expr()->eq('cl.company_id', ':companyId')
            )->setParameter('leadId', $leadId)
            ->setParameter('companyId', $companyId);

        return $qb->executeQuery()->fetchAllAssociative();
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
    public function updateLeadsPrimaryCompanyName(Company $company): void
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
        $leadIds = $q->executeQuery()->fetchOne();
        if (!empty($leadIds)) {
            $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'leads')
            ->set('company', ':company')
            ->setParameter('company', $company->getName())
            ->where(
                $q->expr()->in('id', $leadIds)
            )->executeStatement();
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
        $conn = $this->getEntityManager()->getConnection();
        do {
            $sql = 'DELETE FROM '.MAUTIC_TABLE_PREFIX.'companies_leads WHERE is_primary = 0 LIMIT '.self::DELETE_BATCH_SIZE;
            $row = $conn->executeStatement($sql);
        } while ($row);
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
