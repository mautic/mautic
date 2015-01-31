<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;

/**
 * Class UserSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class UserSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            UserEvents::USER_POST_DELETE => array('onUserDelete', 0)
        );
    }

    /**
     * Delete user's messages when the user is deleted
     *
     * @param UserEvent $event
     */
    public function onUserDelete(UserEvent $event)
    {
        $user = $event->getUser();

        // Delete the chats for this user
        $repo = $this->factory->getEntityManager()->getRepository('MauticChatBundle:Chat');
        $repo->deleteUserMessages($user->deletedId);
    }
}