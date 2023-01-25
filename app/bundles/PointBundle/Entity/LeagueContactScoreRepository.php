<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\OperatorListTrait;

/**
 * @extends CommonRepository<LeagueContactScore>
 */
class LeagueContactScoreRepository extends CommonRepository
{
    use OperatorListTrait;

    public function adjustPoints(Lead $contact, League $league, int $points, string $operator = Lead::POINTS_ADD): Lead
    {
        $contactScore = $this->findOneBy(['contact' => $contact, 'league' => $league]);
        if (empty($contactScore)) {
            $contactScore = new LeagueContactScore();
            $contactScore->setContact($contact);
            $contactScore->setLeague($league);
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

    public function compareValue(int $leadId, int $leagueId, int $score, string $operatorExpr): bool
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('lcs.contact_id')
            ->from(MAUTIC_TABLE_PREFIX.LeagueContactScore::TABLE_NAME, 'lcs');

        $expr = $q->expr()->andX(
            $q->expr()->eq('lcs.contact_id', ':lead'),
            $q->expr()->eq('lcs.league_id', ':leagueId'),
            $q->expr()->$operatorExpr('lcs.score', ':score'),
        );

        $q->where($expr)
            ->setParameter('lead', $leadId)
            ->setParameter('leagueId', $leagueId)
            ->setParameter('score', $score);

        return false !== $q->execute()->fetchOne();
    }
}
