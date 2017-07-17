<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="card" style="height: <?php echo $widget->getHeight() ? ($widget->getHeight() - 10).'px' : '300px' ?>">
    <div class="card-header">
        <h4><?php echo $widget->getName(); ?></h4>
        <?php if ($widget->getId()) : ?>
        <div class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-ellipsis-v"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <li>
                    <a  href="<?php echo $view['router']->generate('mautic_dashboard_action', ['objectAction' => 'edit', 'objectId' => $widget->getId()]); ?>"
                        data-toggle="ajaxmodal"
                        data-target="#MauticSharedModal"
                        data-header="<?php echo $view['translator']->trans('mautic.dashboard.widget.header.edit'); ?>">
                        <i class="fa fa-pencil"></i> Edit
                    </a>
                </li>
                <li role="separator" class="divider"></li>
                <li  class="dropdown-header">
                    <?php echo $view['translator']->trans('mautic.dashboard.widget.load.time', ['%time%' => round($widget->getLoadTime() * 1000, 1)]); ?>
                </li>
                <li  class="dropdown-header">
                    <?php if ($widget->isCached()) : ?>
                    <?php echo $view['translator']->trans('mautic.dashboard.widget.data.loaded.from.cache'); ?>
                    <?php else : ?>
                    <?php echo $view['translator']->trans('mautic.dashboard.widget.data.loaded.from.database'); ?>
                    <?php endif; ?>
                </li>
                <li role="separator" class="divider"></li>
                <li>
                    <a  href="<?php echo $view['router']->generate('mautic_dashboard_action', ['objectAction' => 'delete', 'objectId' => $widget->getId()]); ?>"
                        data-header="<?php echo $view['translator']->trans('mautic.dashboard.widget.header.delete'); ?>"
                        class="remove-widget">
                        <i class="fa fa-remove"></i> Remove
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($widget->getErrorMessage()) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $view['translator']->trans($widget->getErrorMessage()); ?>
            </div>
        <?php elseif ($widget->getTemplate()) : ?>
            <?php echo $view->render($widget->getTemplate(), $widget->getTemplateData()); ?>
        <?php endif; ?>
    </div>
</div>
