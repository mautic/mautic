<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:Builder:index.html.php');
}

if (!isset($expanded))
    $expanded = 'fields';

$fieldExpanded   = ($expanded == 'fields') ? ' in' : '';
$actionExpanded  = (empty($fieldExpanded)) ? ' in' : '';
?>
<div class="bundle-list">
    <div class="panel-group" id="form-components">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#form-components" href="#form-fields">
                        <?php echo $view['translator']->trans('mautic.form.form.component.fields'); ?>
                    </a>
                </h4>
            </div>
            <div id="form-fields" class="panel-collapse collapse<?php echo $fieldExpanded; ?>">
                <div class="panel-body">
                    <?php foreach ($fields as $fieldType => $field): ?>
                    <a data-toggle="ajax" data-ignore-formexit="true" href="<?php echo $view['router']->generate(
                        'mautic_formfield_action',
                        array('objectAction' => 'new', 'type' => $fieldType, 'tmpl' => 'field')); ?>">
                        <div class="bundle-list-item">
                            <div class="padding-sm">
                                <div class="pull-left padding-sm">
                                    <span class="list-item-primary"><?php echo $field; ?></span>
                                </div>
                                <div class="pull-right padding-sm">
                                    <i class="fa fa-fw fa-plus fa-lg"></i>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#form-components" href="#form-submitactions">
                        <?php echo $view['translator']->trans('mautic.form.form.component.submitactions'); ?>
                    </a>
                </h4>
            </div>
            <div id="form-submitactions" class="panel-collapse collapse<?php echo $actionExpanded; ?>">
                <div class="panel-body">
                    <?php foreach ($actions['groupOrder'] as $group => $groupActions): ?>
                    <div class="form-submitaction-group-header"><?php echo $group; ?></div>
                    <div class="form-submitaction-group-body">
                        <?php foreach ($groupActions as $k): ?>
                        <?php $action = $actions['actions'][$k]; ?>
                        <a data-toggle="ajax" data-ignore-formexit="true" href="<?php echo $view['router']->generate(
                            'mautic_formaction_action',
                            array('objectAction' => 'new', 'type' => $k, 'tmpl' => 'action')); ?>">
                            <div class="bundle-list-item">
                                <div class="padding-sm">
                                    <div class="pull-left padding-sm">
                                        <span class="list-item-primary"><?php echo $view['translator']->trans($action['label']); ?></span>
                                        <?php if (isset($action['descr'])): ?>
                                        <span class="list-item-secondary"><?php echo  $view['translator']->trans($action['descr']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pull-right padding-sm">
                                        <i class="fa fa-fw fa-plus fa-lg"></i>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-margin"></div>
</div>