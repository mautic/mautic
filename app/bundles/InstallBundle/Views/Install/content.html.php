<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$app->getRequest()->isXmlHttpRequest() && $view['slots']->get('contentOnly', false) === false):
    //load base template
    $view->extend('MauticInstallBundle:Install:base.html.php');
endif;
$header = $view['slots']->get('pageHeader');
?>

<div class="pt-120 pb-20 content-body" id="main-content">
    <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>

    <div class="container-fluid">
        <?php if (!empty($header)): ?>
        <h2 class="page-header">
            <?php echo $view['translator']->trans($header); ?>
        </h2>
        <?php endif; ?>
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
