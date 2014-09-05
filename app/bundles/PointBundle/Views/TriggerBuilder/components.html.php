<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticPointBundle:TriggerBuilder:index.html.php');
}
?>

<div id="trigger-events">
    <?php foreach ($events as $k => $a): ?>
        <?php if ($newGroup = (empty($lastGroup) || $lastGroup != $e['group'])): ?>
            <div class="trigger-event-group-header"><?php echo $e['group']; ?></div>
            <div class="trigger-event-group-body">
        <?php endif; ?>
        <a data-toggle="ajax" data-ignore-formexit="true" href="<?php echo $view['router']->generate(
            'mautic_pointaction_action',
            array('objectAction' => 'new', 'type' => $k, 'tmpl' => 'action')); ?>">
            <div class="page-list-item">
                <div class="padding-sm">
                    <div class="pull-left padding-sm">
                        <span class="list-item-primary"><?php echo $view['translator']->trans($e['label']); ?></span>
                        <?php if (isset($e['description'])): ?>
                            <span class="list-item-secondary"><?php echo  $view['translator']->trans($e['description']); ?></span>
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
        <?php $lastGroup = $e['group']; ?>
    <?php endforeach; ?>
</div>