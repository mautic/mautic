<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="panel panel-default">
    <h3 class="panel-heading">
        <?php echo $module->getName(); ?>
        <a class="pull-right btn-xs text-danger" 
            href="<?php echo $this->container->get('router')->generate('mautic_dashboard_action', array('objectAction' => 'delete', 'objectId' => $module->getId())); ?>" 
            data-header="<?php echo $view['translator']->trans('mautic.dashboard.module.header.delete'); ?>">
            <i class="fa fa-remove"></i>
        </a>
        <a class="pull-right btn-xs" 
            href="<?php echo $this->container->get('router')->generate('mautic_dashboard_action', array('objectAction' => 'edit', 'objectId' => $module->getId())); ?>" 
            data-toggle="ajaxmodal" 
            data-target="#MauticSharedModal" 
            data-header="<?php echo $view['translator']->trans('mautic.dashboard.module.header.edit'); ?>">
            <i class="fa fa-pencil"></i>
        </a>
    </h3>
    <div class="panel-body">
        <?php if ($module->getErrorMessage()) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $view['translator']->trans($module->getErrorMessage()); ?>
            </div>
        <?php elseif ($module->getTemplate()) : ?>
            <?php echo $view->render($module->getTemplate(), $module->getTemplateData()); ?>
        <?php endif; ?>
    </div>
</div>
