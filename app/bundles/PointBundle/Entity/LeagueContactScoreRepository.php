<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;

/**
 * @extends CommonRepository<LeagueContactScore>
 */
class LeagueContactScoreRepository extends CommonRepository
{
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
}
