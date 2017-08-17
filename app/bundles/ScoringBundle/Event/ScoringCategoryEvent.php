<?php

/*
 * @author      Captivea (QCH)
 */

namespace Mautic\ScoringBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\ScoringBundle\Entity\ScoringCategory;

/**
 * Class ScoringCategoryEvent.
 */
class ScoringCategoryEvent extends CommonEvent
{
    /**
     * @param ScoringCategory $scoringCategory
     * @param bool            $isNew
     */
    public function __construct(ScoringCategory &$scoringCategory, $isNew = false)
    {
        $this->entity = &$scoringCategory;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the ScoringCategory entity.
     *
     * @return ScoringCategory
     */
    public function getScoringCategory()
    {
        return $this->entity;
    }

    /**
     * Sets the ScoringCategory entity.
     *
     * @param ScoringCategory $scoringCategory
     */
    public function setScoringCategory(ScoringCategory $scoringCategory)
    {
        $this->entity = $scoringCategory;
    }
}
