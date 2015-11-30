<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="panel panel-default mb-0" style="height: <?php echo !empty($module->getHeight()) ? $module->getHeight() . 'px' : '300px' ?>">
    <h3 class="panel-heading">
        <?php echo $module->getName(); ?>
        <a class="pull-right btn-xs" 
            href="<?php echo $this->container->get('router')->generate('mautic_dashboard_action', array('objectAction' => 'edit', 'objectId' => $module->getId())); ?>" 
            data-toggle="ajaxmodal" 
            data-target="#MauticSharedModal" 
            data-header="<?php echo $view['translator']->trans('mautic.lead.note.header.edit'); ?>">
            <i class="fa fa-pencil"></i>
        </a>
    </h3>
</div>
