<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($alertType)) {
    $alertType = 'growl';
}

?>
<div id="flashes"<?php echo ($alertType == 'growl') ? ' class="alert-growl-container"' : ''; ?>>
    <?php echo $view->render('MauticCoreBundle:Notification:flash_messages.html.php', [
        'dismissible' => (empty($notdismissible)) ? ' alert-dismissible' : '',
        'alertType'   => $alertType,
    ]); ?>
</div>