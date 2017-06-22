<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticMessengerBundle\Entity\MessengerMessage;

class MessengerMessageEvent extends CommonEvent
{
    /**
     * MessengerMessageEvent constructor.
     *
     * @param MessengerMessage $entity
     * @param bool           $isNew
     */
    public function __construct(MessengerMessage $entity, $isNew = false)
    {
        $this->entity = $entity;
        $this->isNew  = $isNew;
    }

    /**
     * @return MessengerMessage
     */
    public function getMessengerMessage()
    {
        return $this->entity;
    }

    /**
     * @param MessengerMessage $entity
     */
    public function setMessengerMessage(MessengerMessage $entity)
    {
        $this->entity = $entity;
    }
}
