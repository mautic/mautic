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

<!-- start: sidebar header -->
<div class="sidebar-header box-layout">
    <div class="col-xs-6 va-m">
        <h5 class="fw-sb"><?php echo $view['translator']->trans('mautic.chat.chat.channels'); ?></h5>
    </div>
    <div class="col-xs-6 va-m text-right">
        <!-- this will toggle offcanvas-left container-->
        <a href="javascript:void(0);" class="btn btn-primary offcanvas-opener offcanvas-open-ltr">Add</a>
    </div>
</div>
<!--/ end: sidebar header -->

<!-- start: sidebar content -->
<div class="sidebar-content">
    <!-- scroll-content -->
    <div class="scroll-content slimscroll">
        <?php echo $view->render('MauticChatBundle:Default:channels.html.php', array('channels' => $channels)); ?>

        <!-- put the chat list here -->
        <?php echo $view->render('MauticChatBundle:Default:users.html.php', array('users' => $users)); ?>
    </div>
    <!--/ scroll-content -->
</div>
<!--/ end: sidebar content -->