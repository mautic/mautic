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
<div class="main-panel-header">
    <?php echo $view->render('MauticCoreBundle:Default:toolbar.html.php'); ?>
    <div class="clearfix"></div>
</div>

<div class="main-panel-content-wrapper">
    <?php $view['slots']->output('_content'); ?>
    <div class="main-panel-footer"></div>
</div>

<?php $view['slots']->output('modal'); ?>