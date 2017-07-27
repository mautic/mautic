<?php

namespace Mautic\ScoringBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Description of ScoringValue
 *
 * @author captivea-qch
 */
class ScoringValue {
    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;
    
    /**
     * @var \Mautic\ScoringBundle\Entity\ScoringCategory
     */
    private $scoringCategory;
    
    /**
     *
     * @var float
     */
    private $score;
    
    
    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata) {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('scoring_values')
            ->setCustomRepositoryClass('Mautic\ScoringBundle\Entity\ScoringValueRepository');
        
        $builder->addLead(false, 'CASCADE', true, 'scoringValues');
        $builder->createManyToOne('scoringCategory', 'Mautic\ScoringBundle\Entity\ScoringCategory')
                ->isPrimaryKey()
                ->addJoinColumn('scoringcategory_id', 'id', true, false, 'CASCADE')
                ->build();

        $builder->createField('score', 'integer')
            ->build();
    }
    
    /**
     * Set lead.
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     *
     * @return $this
     */
    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead = null) {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead.
     *
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead() {
        return $this->lead;
    }
    
    /**
     * Set scoringCategory.
     *
     * @param \Mautic\ScoringBundle\Entity\ScoringCategory $scoringCategory
     *
     * @return $this
     */
    public function setScoringCategory(ScoringCategory $scoringCategory = null) {
        $this->scoringCategory = $scoringCategory;

        return $this;
    }

    /**
     * Get scoringCategory.
     *
     * @return \Mautic\ScoringBundle\Entity\ScoringCategory
     */
    public function getScoringCategory() {
        return $this->scoringCategory;
    }
    
    /**
     * 
     * @param integer $score
     * @return $this
     */
    public function setScore($score) {
        $this->score = intval($score);
        return $this;
    }
    
    /**
     * 
     * @return integer
     */
    public function getScore() {
        return $this->score;
    }
}
