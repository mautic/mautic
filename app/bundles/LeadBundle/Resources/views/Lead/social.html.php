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
                <?php if (!empty($details['profile']['profileImage'])): ?>
                <div class="col-sm-3">
                    <img class="img img-responsive img-thumbnail" src="<?php echo $details['profile']['profileImage']; ?>" />
                </div>
                <?php
                    unset($details['profile']['profileImage']);
                    $col = 9; ?>
                <?php endif; ?>
                <div class="col-sm-<?php echo $col; ?>">
                    <ul class="nav nav-tabs" role="tablist">
                        <?php if (!empty($details['profile']) && !empty($details['activity'])): ?>
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
                        <?php elseif (!empty($details['profile'])): ?>
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
                        <?php if (!empty($details['profile'])): ?>
                        <div class="tab-pane<?php echo ($active == 'profile') ? ' active': ''; ?>" id="<?php echo $network; ?>Profile">
                            <?php echo $view->render('MauticLeadBundle:Social/' . $network . ':profile.html.php', array(
                                'lead'              => $lead,
                                'profile'           => $details['profile'],
                                'dateFormats'       => $dateFormats,
                                'network'           => $network,
                                'socialProfileUrls' => $socialProfileUrls
                            )); ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($details['activity'])): ?>
                        <div class="tab-pane<?php echo ($active == 'activity') ? ' active': ''; ?>" id="<?php echo $network; ?>Activity">
                            <?php echo $view->render('MauticLeadBundle:Social/' . $network . ':activity.html.php', array(
                                    'lead'        => $lead,
                                    'activity'    => $details['activity'],
                                    'dateFormats' => $dateFormats,
                                    'network'     => $network
                            )); ?>
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