<?php
namespace Mautic\ScoringBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Description of ScoringValueRepository
 *
 * @author captivea-qch
 */
class ScoringValueRepository extends CommonRepository {
    /**
     * 
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param ScoringCategory $scoringCategory
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function adjustPoints($lead, $scoringCategory, $points, $operator = 'plus') {
        $scoringValue = $this->findBy(array('lead' => $lead, 'scoringCategory' => $scoringCategory));
        if(empty($scoringValue)) {
            $scoringValue = new ScoringValue;
            $scoringValue->setLead($lead);
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
            $lead->adjustPoints($modifiedPoints, $operator);
        }
        
        return $lead;
    }
}
