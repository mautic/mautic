<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;

/**
 * @extends CommonRepository<CompanyLead>
 */
class CompanyLeadRepository extends CommonRepository
{
    public const DELETE_BATCH_SIZE = 1000;

    public const BATCH_SIZE        = 5000;

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
                    ->set('is_primary', 'FALSE');

                $qb->where(
                    $qb->expr()->in('lead_id', ':leadIds')
                )->setParameter('leadIds', $contacts, ArrayParameterType::INTEGER)
                    ->executeStatement();
            }
        }

        parent::saveEntities($entities);
    }

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
                $q->expr()->eq('cl.is_primary', 'TRUE')
            );
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int[] $ids
     *
     * @return list<array<string, mixed>>
     */
    public function getPrimaryCompaniesByLeadIds(array $ids): array
    {
        $ids = array_filter($ids);

        if ([] === $ids) {
            return [];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('comp.*')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp')
            ->join('comp', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', 'cl.company_id = comp.id')
            ->andWhere('cl.is_primary = TRUE')
            ->andWhere('cl.lead_id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

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
            fn (array $company): string => (string) $company['company_id'],
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
            ->where(
                $q->expr()->eq('cl.lead_id', ':leadId'),
                $q->expr()->isNull('comp.deleted')
            )
            ->setParameter('leadId', $leadId);
        $q->orderBy('cl.date_added', 'DESC');

        $result = $q->executeQuery()->fetchAllAssociative();

        return $result[0] ?? [];
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
        $this->updateCompanyNameOnLeads($company);
    }

    public function updateCompanyNameOnLeads(Company $company): void
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = cl.lead_id')
            ->where($q->expr()->eq('cl.company_id', ':companyId'))
            ->setParameter('companyId', $company->getId())
            ->andWhere($q->expr()->neq('l.company', ':company'))
            ->setParameter('company', $company->getName())
            ->andWhere('cl.is_primary = TRUE')
            ->setMaxResults(self::BATCH_SIZE);
        while ($leadIds = $q->executeQuery()->fetchFirstColumn()) {
            $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->update(MAUTIC_TABLE_PREFIX.'leads')
                ->set('company', ':company')
                ->setParameter('company', $company->getName())
                ->where(
                    $q->expr()->in('id', ':leadIds')
                )
                ->setParameter('leadIds', $leadIds, ArrayParameterType::INTEGER)
                ->executeStatement();
        }
    }

    public function deleteCompanyLeads(int $companyId): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $q    = $conn->createQueryBuilder();
        $q->select('company_id', 'lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads')
            ->where($q->expr()->eq('company_id', ':companyId'))
            ->setParameter('companyId', $companyId, ParameterType::INTEGER)
            ->setMaxResults(self::BATCH_SIZE);

        while ($pairs = $q->executeQuery()->fetchAllAssociative()) {
            $deleteQb = $conn->createQueryBuilder();

            $deleteQb
                ->delete(MAUTIC_TABLE_PREFIX.'companies_leads')
                ->where(
                    $deleteQb->expr()->in(
                        '(company_id, lead_id)',
                        array_map(
                            static fn (array $pair): string => '('.(int) $pair['company_id'].', '.(int) $pair['lead_id'].')',
                            $pairs
                        )
                    )
                )
                ->executeStatement();
        }
    }

    public function removeContactPrimaryCompany(int $leadId): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads');
        $qb->where(
            $qb->expr()->eq('lead_id', $leadId)
        )->andWhere(
            $qb->expr()->eq('is_primary', 'TRUE')
        )->executeStatement();
    }

    public function removeAllSecondaryCompanies(): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $q = $conn->createQueryBuilder();
        $q->select('company_id', 'lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads')
            ->where($q->expr()->eq('is_primary', ':isPrimary'))
            ->setParameter('isPrimary', false, ParameterType::BOOLEAN)
            ->setMaxResults(self::DELETE_BATCH_SIZE);

        while ($pairs = $q->executeQuery()->fetchAllAssociative()) {
            $deleteQb = $conn->createQueryBuilder();

            $deleteQb
                ->delete(MAUTIC_TABLE_PREFIX.'companies_leads')
                ->where(
                    $deleteQb->expr()->in(
                        '(company_id, lead_id)',
                        array_map(
                            static fn (array $pair): string => '('.(int) $pair['company_id'].', '.(int) $pair['lead_id'].')',
                            $pairs
                        )
                    )
                )
                ->executeStatement();
        }
    }

    public function removeContactSecondaryCompanies(int $leadId): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'companies_leads');
        $qb->where(
            $qb->expr()->eq('lead_id', $leadId)
        )->andWhere(
            $qb->expr()->eq('is_primary', 'FALSE')
        )->executeStatement();
    }
}
