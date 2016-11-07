<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!$app->getRequest()->isXmlHttpRequest() && $view['slots']->get('contentOnly', false) === false) :
    //load base template
    $view->extend('MauticInstallBundle:Install:base.html.php');
endif;
?>

<?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php'); ?>

<?php $view['slots']->output('_content'); ?>
