<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;

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

        switch ($operator) {
            case Lead::POINTS_ADD:
                $oldScore += $points;
                break;
            case Lead::POINTS_SUBTRACT:
                $oldScore -= $points;
                break;
            case Lead::POINTS_MULTIPLY:
                $oldScore *= $points;
                break;
            case Lead::POINTS_DIVIDE:
                $oldScore /= $points;
                break;
            default:
                throw new \UnexpectedValueException('Invalid operator');
        }
        $contactScore->setScore($oldScore);
        $this->_em->persist($contactScore);
        $this->_em->flush();

        return $contact;
    }
}
