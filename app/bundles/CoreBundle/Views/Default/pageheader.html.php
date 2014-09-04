<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="page-header">
    <div class="table-layout">
        <div class="col-sm-6 va-m">
            <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
        </div>
        <div class="col-sm-6 va-m">
            <div class="toolbar" id="toolbar">
                <?php $view['slots']->output('actions'); ?>

                <div class="toolbar-bundle-buttons pull-left"><?php $view['slots']->output('toolbar'); ?></div>
                <div class="toolbar-form-buttons hide pull-right btn-group"></div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>