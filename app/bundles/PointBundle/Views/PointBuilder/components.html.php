<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticPointBundle:PointBuilder:index.html.php');
}
?>

<div id="point-actions">
    <?php foreach ($actions as $k => $a): ?>
        <?php if ($newGroup = (empty($lastGroup) || $lastGroup != $a['group'])): ?>
            <div class="poing-action-group-header"><?php echo $a['group']; ?></div>
            <div class="poing-action-group-body">
        <?php endif; ?>
        <a data-toggle="ajax" data-ignore-formexit="true" href="<?php echo $view['router']->generate(
            'mautic_pointaction_action',
            array('objectAction' => 'new', 'type' => $k, 'tmpl' => 'action')); ?>">
            <div class="page-list-item">
                <div class="padding-sm">
                    <div class="pull-left padding-sm">
                        <span class="list-item-primary"><?php echo $view['translator']->trans($a['label']); ?></span>
                        <?php if (isset($a['description'])): ?>
                            <span class="list-item-secondary"><?php echo  $view['translator']->trans($a['description']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pull-right padding-sm">
                        <i class="fa fa-fw fa-plus fa-lg"></i>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </a>
        <?php if ($newGroup): ?>
            </div>
        <?php endif; ?>
        <?php $lastGroup = $a['group']; ?>
    <?php endforeach; ?>
</div>