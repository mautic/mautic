<?php

namespace Mautic\ScoringBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Description of ScoringCompanyValueRepository.
 *
 * @author captivea-qch
 */
class ScoringCompanyValueRepository extends CommonRepository
{
    /**
     * @param \Mautic\LeadBundle\Entity\Company $company
     * @param ScoringCategory                   $scoringCategory
     *
     * @return int
     */
    public function adjustPoints(\Mautic\LeadBundle\Entity\Company $company, ScoringCategory $scoringCategory, $points, $operator = 'plus')
    {
        $modifiedPoints = 0;
        if (!empty($company)) {
            $scoringValue = $this->findOneBy(['company' => $company, 'scoringCategory' => $scoringCategory]);
            if (empty($scoringValue)) {
                $scoringValue = new ScoringCompanyValue();
                $scoringValue->setCompany($company);
                $scoringValue->setScoringCategory($scoringCategory);
                $scoringValue->setScore(0);
            }
            $oldScore = $scoringValue->getScore();
            switch ($operator) {
                case 'plus':
                    $oldScore += $points;
                    break;
                case 'minus':
                    $oldScore -= $points;
                    break;
                case 'times':
                    $oldScore *= $points;
                    break;
                case 'divide':
                    $oldScore /= $points;
                    break;
                default:
                    throw new \UnexpectedValueException('Invalid operator');
            }
            $scoringValue->setScore($oldScore);
            $this->_em->persist($scoringValue);
            $this->_em->flush();

            if ($scoringCategory->getUpdateGlobalScore()) {
                $modifier       = $scoringCategory->getGlobalScoreModifier();
                $modifiedPoints = round(($modifier * $points) / 100);
            }
        }

        return $modifiedPoints;
    }
}
