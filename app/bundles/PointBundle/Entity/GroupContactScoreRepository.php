<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;

/**
 * @extends CommonRepository<GroupContactScore>
 */
class GroupContactScoreRepository extends CommonRepository
{
    public function adjustPoints(Lead $contact, Group $group, int $points, string $operator = Lead::POINTS_ADD): Lead
    {
        $contactScore = $this->findOneBy(['contact' => $contact, 'group' => $group]);
        if (empty($contactScore)) {
            $contactScore = new GroupContactScore();
            $contactScore->setContact($contact);
            $contactScore->setGroup($group);
            $contactScore->setScore(0);
        }
        $oldScore = $contactScore->getScore();
        $newScore = $oldScore;

        switch ($operator) {
            case Lead::POINTS_ADD:
                $newScore += $points;
                break;
            case Lead::POINTS_SUBTRACT:
                $newScore -= $points;
                break;
            case Lead::POINTS_MULTIPLY:
                $newScore *= $points;
                break;
            case Lead::POINTS_DIVIDE:
                $newScore /= $points;
                break;
            default:
                throw new \UnexpectedValueException('Invalid operator');
        }
        $contactScore->setScore($newScore);
        $this->_em->persist($contactScore);
        $this->_em->flush();

        return $contact;
    }

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
