<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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

<?php echo $view->render('MauticChatBundle:Default:channels.html.php', array(
    'channels'    => $channels,
    'permissions' => $permissions
)); ?>
<?php echo $view->render('MauticChatBundle:Default:users.html.php', array('users' => $users)); ?>
<?php
if (empty($ignoreModal)):
    echo $this->render('MauticCoreBundle:Helper:modal.html.php', array('id' => 'channelModal'));
endif;
?>