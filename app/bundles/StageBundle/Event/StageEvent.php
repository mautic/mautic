<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\StageBundle\Entity\Stage;

final class StageEvent extends CommonEvent
{
    /**
     * @param bool $isNew
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
     */
    public function setStage(Stage $stage): void
    {
        $this->entity = $stage;
    }
}
