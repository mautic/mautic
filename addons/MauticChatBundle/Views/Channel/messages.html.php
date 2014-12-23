<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$myId            = $me->getId();
$grouped         = array();
$lastUser        = '';
$dividerInserted = false;

foreach ($messages as $num => $dm) {
    //get the next for comparison
    $next = (isset($messages[$num+1])) ? $messages[$num+1] : false;

    if (empty($grouped)) {
        //first message of the group
        $nextDate  = ($next) ? $view['date']->toShort($next['dateSent']) : $view['date']->toShort($dm['dateSent']);
        $direction = ($dm['fromUser']['id'] === $myId) ? '' : ' media-right';
    }
    $msgDate = $view['date']->toShort($dm['dateSent']);

    //add the dm
    $grouped[] = $dm;

    if (!$next || $next['fromUser']['id'] !== $dm['fromUser']['id'] || $msgDate != $nextDate) {
        //last message or new group

        //now render the messages
        echo $view->render('MauticChatBundle:Default:group.html.php', array(
            'direction'           => $direction,
            'messages'            => $grouped,
            'user'                => $dm['fromUser'],
            'showDate'            => ($msgDate != $nextDate),
            'insertUnreadDivider' => (!empty($insertUnreadDivider)) ? true : false,
            'lastReadId'          => $lastReadId
        ));

        //reset the group
        $grouped = array();
    } else {
        //next message from same user so add it and move to the next
        continue;
    }
}