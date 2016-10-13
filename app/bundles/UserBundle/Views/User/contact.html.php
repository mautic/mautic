<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.user.user.header.contact', ['%name%' => $user->getName()]));
?>

<div class="panel">
    <div class="panel-body pa-md">
        <?php echo $view['form']->form($form); ?>
    </div>
</div>
