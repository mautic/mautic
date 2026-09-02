<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<StagesChangeLog>
 */
class StagesChangeLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get a lead's stage log.
     *
     * @param int|null             $leadId
     * @param array<string, mixed> $options
     *
     * @return array
     */
    public function getLeadTimelineEvents($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'lead_stages_change_log', 'ls')
            ->select('ls.id, ls.stage_id as reference, ls.event_name as eventName, ls.action_name as actionName, ls.date_added as dateAdded, ls.lead_id');

        if ($leadId) {
            $query->where('ls.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->or(
                    $query->expr()->like('ls.event_name', ':search'),
                    $query->expr()->like('ls.action_name', ':search')
                )
            )->setParameter('search', '%'.$options['search'].'%');
        }

        return $this->getTimelineResults($query, $options, 'ls.event_name', 'ls.date_added', [], ['dateAdded'], null, 'ls.id');
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead(string $fromLeadId, string $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_stages_change_log')
            ->set('lead_id', ':to')
            ->where('lead_id = :from')
            ->setParameter('to', $toLeadId)
            ->setParameter('from', $fromLeadId)
            ->executeStatement();
    }

    public function updateStage(int $fromStageId, int $toStageId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_stages_change_log')
            ->set('stage_id', ':to')
            ->where('stage_id = :from')
            ->setParameter('to', $toStageId, ParameterType::INTEGER)
            ->setParameter('from', $fromStageId, ParameterType::INTEGER)
            ->executeStatement();
    }

    /**
     * Get the current stage assigned to a lead.
     *
     * @param int $leadId
     */
    public function getCurrentLeadStage($leadId): ?int
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('stage_id as stage')
            ->from(MAUTIC_TABLE_PREFIX.'lead_stages_change_log', 'ls')
            ->where($query->expr()->eq('lead_id', ':value'))
            ->setParameter('value', $leadId)
            ->orderBy('date_added', 'DESC');

        $result = $query->executeQuery()->fetchAssociative();

        return (isset($result['stage'])) ? (int) $result['stage'] : null;
    }
}
