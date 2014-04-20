<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-toggle left-panel-toggle pull-left">
    <a href="javascript: void(0);" onclick="Mautic.toggleSidePanel('left');"><i class="fa fa-bars fa-2x"></i></a>
</div>

<div class="top-panel-main pull-left">
    <?php echo $view->render("MauticCoreBundle:Menu:admin.html.php"); ?>
</div>

<div class="pull-right account-menu">
    <?php echo $view->render("MauticCoreBundle:Menu:profile.html.php"); ?>
</div>