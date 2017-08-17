<?php

namespace Mautic\ScoringBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Description of ScoringCompanyValue.
 *
 * @author captivea-qch
 */
class ScoringCompanyValue
{
    /**
     * @var \Mautic\LeadBundle\Entity\Company
     */
    private $company;

    /**
     * @var \Mautic\ScoringBundle\Entity\ScoringCategory
     */
    private $scoringCategory;

    /**
     * @var float
     */
    private $score;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('scoring_company_values')
            ->setCustomRepositoryClass('Mautic\ScoringBundle\Entity\ScoringCompanyValueRepository');

        $builder->createManyToOne('company', 'Mautic\LeadBundle\Entity\Company')
                ->isPrimaryKey()
                ->addJoinColumn('company_id', 'id', true, false, 'CASCADE')
                ->build();
        $builder->createManyToOne('scoringCategory', 'Mautic\ScoringBundle\Entity\ScoringCategory')
                ->isPrimaryKey()
                ->addJoinColumn('scoringcategory_id', 'id', true, false, 'CASCADE')
                ->build();

        $builder->createField('score', 'integer')
            ->build();
    }

    /**
     * Set company.
     *
     * @param \Mautic\LeadBundle\Entity\Company $company
     *
     * @return $this
     */
    public function setCompany(\Mautic\LeadBundle\Entity\Company $company = null)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company.
     *
     * @return \Mautic\LeadBundle\Entity\Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set scoringCategory.
     *
     * @param \Mautic\ScoringBundle\Entity\ScoringCategory $scoringCategory
     *
     * @return $this
     */
    public function setScoringCategory(ScoringCategory $scoringCategory = null)
    {
        $this->scoringCategory = $scoringCategory;

        return $this;
    }

    /**
     * Get scoringCategory.
     *
     * @return \Mautic\ScoringBundle\Entity\ScoringCategory
     */
    public function getScoringCategory()
    {
        return $this->scoringCategory;
    }

    /**
     * @param int $score
     *
     * @return $this
     */
    public function setScore($score)
    {
        $this->score = intval($score);

        return $this;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }
}
