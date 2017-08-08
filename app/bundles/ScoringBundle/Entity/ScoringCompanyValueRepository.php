<?php
namespace Mautic\ScoringBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Description of ScoringCompanyValueRepository
 *
 * @author captivea-qch
 */
class ScoringCompanyValueRepository extends CommonRepository {
    /**
     * 
     * @param \Mautic\LeadBundle\Entity\Company $company
     * @param ScoringCategory $scoringCategory
     * @return \Mautic\LeadBundle\Entity\Company
     */
    public function adjustPoints(\Mautic\LeadBundle\Entity\Company $company, ScoringCategory $scoringCategory, $points, $operator = 'plus') {
        if(!empty($company)) {
            $scoringValue = $this->findOneBy(array('company' => $company, 'scoringCategory' => $scoringCategory));
            if(empty($scoringValue)) {
                $scoringValue = new ScoringCompanyValue;
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
            $this->_em->persist($scoringValue);
            $this->_em->flush();

            if($scoringCategory->getUpdateGlobalScore()) {
                $modifier = $scoringCategory->getGlobalScoreModifier();
                $modifiedPoints = round(($modifier * $points) / 100);
                if(in_array($operator, array('plus',))) {
                    $company->setScore($company->getScore() + $modifiedPoints);
                }
            }
        }
        return $company;
    }
}
