<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCampaignBundle:CampaignBuilder:index.html.php');
}

$campaignType = (is_null($form['type']->vars['data'])) ? 0 : $form['type']->vars['data'];
?>

<div id="campaignEventList">
    <?php foreach ($events as $k => $e): ?>
        <?php if ($newGroup = (empty($lastGroup) || $lastGroup != $e['group'])): ?>
            <div class="campaign-event-group-header"><?php echo $e['group']; ?></div>
            <div class="campaign-event-group-body">
        <?php endif; ?>
        <a data-toggle="ajaxmodal" data-target="#campaignEventModal" href="<?php echo $view['router']->generate(
            'mautic_campaignevent_action', array(
                'objectAction' => 'new',
                'type'         => $k,
                'campaignType' => $campaignType
            )); ?>">
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