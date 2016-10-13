<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;

/**
 * Class SocialEvent.
 */
class SocialEvent extends CommonEvent
{
    /**
     * @param Monitoring $monitoring
     * @param bool       $isNew
     */
    public function __construct(Monitoring $monitoring, $isNew = false)
    {
        $this->entity = $monitoring;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Monitoring entity.
     *
     * @return Monitoring
     */
    public function getMonitoring()
    {
        return $this->entity;
    }

    /**
     * Sets the Monitoring entity.
     *
     * @param Monitoring $monitoring
     */
    public function setMonitoring(Monitoring $monitoring)
    {
        $this->entity = $monitoring;
    }
}
