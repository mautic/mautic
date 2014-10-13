<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$app->getRequest()->isXmlHttpRequest() && $view['slots']->get('contentOnly', false) === false):
    //load base template
    $view->extend('MauticInstallBundle:Install:base.html.php');
endif;
?>

<div class="pt-120 pb-20 content-body" id="main-content">
    <?php echo $view->render('MauticInstallBundle:Install:pageheader.html.php'); ?>

    <div class="container-fluid">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>

<?php $view['slots']->output('modal'); ?>
