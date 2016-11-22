<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\StageBundle\Entity\Stage;

/**
 * Class StageEvent.
 */
class StageEvent extends CommonEvent
{
    /**
     * @param Stage $stage
     * @param bool  $isNew
     */
    public function __construct(Stage &$stage, $isNew = false)
    {
        $this->entity = &$stage;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Stage entity.
     *
     * @return Stage
     */
    public function getStage()
    {
        return $this->entity;
    }

    /**
     * Sets the Stage entity.
     *
     * @param Stage $stage
     */
    public function setStage(Stage $stage)
    {
        $this->entity = $stage;
    }
}
