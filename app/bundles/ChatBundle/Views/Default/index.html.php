<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!empty($inPopup)) {
    $view->extend('MauticCoreBundle:Default:slim.html.php');
    //$view['assets']->addScriptDeclaration("Mautic.activateChatInput('{$with->getId()}');", 'bodyClose');
}

if (empty($contentOnly)) {
    $view['assets']->addScriptDeclaration('Mautic.activateChatListUpdate();', 'bodyClose');
}
?>
<div id="ChatList">
    <h5 class="heading"><?php echo $view['translator']->trans('mautic.chat.chat.channels'); ?></h5>
    <?php echo $view->render('MauticChatBundle:Default:channels.html.php', array('channels' => $channels)); ?>

    <h5 class="heading"><?php echo $view['translator']->trans('mautic.chat.chat.users'); ?></h5>
    <?php echo $view->render('MauticChatBundle:Default:users.html.php', array('users' => $users)); ?>
</div>