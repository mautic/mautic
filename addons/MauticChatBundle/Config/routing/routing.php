<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_chat_index', new Route('/chat', array(
    '_controller' => 'MauticChatBundle:Default:index',
)));

$collection->add('mautic_chatchannel_action', new Route('/chat/channel/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticChatBundle:Channel:execute',
        'objectId'    => 0
    )
));

$collection->add('mautic_chat_action', new Route('/chat/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticChatBundle:Default:execute',
        'objectId'    => 0
    )
));

return $collection;