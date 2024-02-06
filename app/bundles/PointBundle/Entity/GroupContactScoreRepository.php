<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<GroupContactScore>
 */
class GroupContactScoreRepository extends CommonRepository
{
    public function compareScore(int $leadId, int $groupId, int $score, string $operatorExpr): bool
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('lcs.contact_id')
            ->from(MAUTIC_TABLE_PREFIX.GroupContactScore::TABLE_NAME, 'lcs');

        $expr = $q->expr()->and(
            $q->expr()->eq('lcs.contact_id', ':lead'),
            $q->expr()->eq('lcs.group_id', ':groupId'),
            $q->expr()->$operatorExpr('lcs.score', ':score'),
        );

        $q->where($expr)
            ->setParameter('lead', $leadId)
            ->setParameter('groupId', $groupId)
            ->setParameter('score', $score);

        return false !== $q->executeQuery()->fetchOne();
    }
}
