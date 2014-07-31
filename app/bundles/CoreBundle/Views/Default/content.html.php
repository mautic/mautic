<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!$app->getRequest()->isXmlHttpRequest()):
    //load base template
    $view->extend('MauticCoreBundle:Default:base.html.php');
endif;
?>

<?php echo $view->render('MauticCoreBundle:Default:pageheader.html.php'); ?>

<div id="page-content">
    <?php $view['slots']->output('_content'); ?>
</div>

<?php $view['slots']->output('modal'); ?>