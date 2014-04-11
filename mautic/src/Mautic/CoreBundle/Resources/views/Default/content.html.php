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
    <?php if ($view["slots"]->has("headerTitle")): ?>
        <div  class="pull-left">
    <h2><?php $view["slots"]->output("headerTitle"); ?></h2>
        </div>
    <?php endif; ?>

    <?php if ($view["slots"]->has("actions") || $view["slots"]->has("filterInput")): ?>
    <div class="pull-right action-buttons">
        <div class="input-group">
            <?php $view["slots"]->output("filterInput", ""); ?>
            <?php if ($view["slots"]->has("actions")): ?>
            <div class="input-group-btn">
                <?php if ($view["slots"]->has("filterInput")): ?>
                <button class="btn btn-default btn-search"
                        onclick="Mautic.filterList(event, '<?php echo $app->getRequest()->getUri(); ?>');"
                        onmouseover="Mautic.showFilterInput();"
                        onmouseout="Mautic.hideFilterInput()">
                    <i class="fa fa-search fa-fw"></i>
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    <span><?php echo $view['translator']->trans('mautic.core.form.actions'); ?></span>
                    <span class="caret"></span>
                </button>

                <ul class="dropdown-menu pull-right">
                    <?php $view['slots']->output('actions', ''); ?>
                </ul>
            </div>
            <?php elseif ($view["slots"]->has("filterInput")): ?>
            <button class="btn btn-default btn-search"
                    onclick="Mautic.filterList(event, '<?php echo $app->getRequest()->getUri(); ?>');">
                <i class="fa fa-search fa-fw"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="clearfix"></div>
</div>

<?php $view['slots']->output('_content'); ?>