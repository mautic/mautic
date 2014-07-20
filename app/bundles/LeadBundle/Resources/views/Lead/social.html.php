<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate score log view
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="panel-title"><?php echo $view['translator']->trans('mautic.lead.lead.header.socialprofiles'); ?></div>
        <div class="panel-toolbar">
            <?php $networks = array_keys($socialProfiles); ?>
            <ul class="nav nav-tabs" role="tablist">
                <?php foreach ($networks as $k => $network): ?>
                <li<?php echo ($k === 0) ? ' class="active"' : ''; ?>>
                    <a href="#<?php echo $network; ?>" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.social.'.$network); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="panel-body">
        <div class="tab-content">
            <?php $count = 0; ?>
            <?php foreach ($socialProfiles as $network => $details): ?>
            <div class="tab-pane<?php echo ($count === 0) ? ' active': ''; ?>" id="<?php echo $network; ?>">
                <?php $col = 12; ?>
                <?php if (!empty($details['data']['profileImage'])): ?>
                <div class="col-sm-3">
                    <img class="img img-responsive img-thumbnail" src="<?php echo $details['data']['profileImage']; ?>" />
                </div>
                <?php
                    unset($details['data']['profileImage']);
                    $col = 9; ?>
                <?php endif; ?>
                <div class="col-sm-<?php echo $col; ?>">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php if (!empty($details['data']) && !empty($details['activity'])): ?>
                        <?php $active = 'profile'; ?>
                        <li class="active">
                            <a href="#<?php echo $network; ?>Profile" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.lead.lead.tab.socialprofile'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#<?php echo $network; ?>Activity" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.lead.lead.tab.socialactivity'); ?>
                            </a>
                        </li>
                        <?php elseif (!empty($details['data'])): ?>
                        <?php $active = 'profile'; ?>
                        <li class="active">
                            <a href="#<?php echo $network; ?>Profile" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.lead.lead.tab.socialprofile'); ?>
                            </a>
                        </li>
                        <?php elseif (!empty($details['activity'])): ?>
                        <?php $active = 'activity'; ?>
                        <li class="active">
                            <a href="#<?php echo $network; ?>Activity" role="tab" data-toggle="tab">
                                <?php echo $view['translator']->trans('mautic.lead.lead.tab.socialactivity'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <?php if (!empty($details['data'])): ?>
                        <div class="tab-pane<?php echo ($active == 'profile') ? ' active': ''; ?>" id="<?php echo $network; ?>Profile">
                            <?php foreach ($details['data'] as $l => $v): ?>
                            <div class="row">
                                <div class="col-xs-3">
                                    <?php echo $view['translator']->trans('mautic.social.'.$network.'.'.$l); ?>
                                </div>
                                <div class="col-xs-9 field-value">
                                    <?php echo $view->render('MauticLeadBundle:Lead:info_value.html.php', array(
                                        'name'       => $l,
                                        'value'      => $v
                                    )); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($details['activity'])): ?>
                        <div class="tab-pane<?php echo ($active == 'activity') ? ' active': ''; ?>" id="<?php echo $network; ?>Activity">
                            <ul>
                                <?php foreach ($details['activity'] as $activity): ?>
                                <li>

                                    <a href="<?php echo $activity['url']; ?>" target="_blank">
                                        <?php echo $view['translator']->trans('mautic.lead.lead.socialactivity.title',array(
                                            '%title%' => $activity['title'],
                                            '%date%'  => date($dateFormats['datetime'], strtotime($activity['published']))
                                        )); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php $count++; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>