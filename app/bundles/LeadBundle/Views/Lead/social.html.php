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

    <?php $count = 0; ?>
    <div class="row">
    <?php foreach ($socialProfiles as $network => $details): ?>
        <?php if ($count > 0 && $count%4 == 0): echo '</div><div class="row">'; endif; ?>
        <div class="col-md-4">
            <div class="panel panel-default panel-<?php echo strtolower($network); ?>">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.social.'.$network); ?></h3>
                    <div class="panel-toolbar text-right">
                        <!-- option -->
                        <div class="option">
                            <button class="btn" data-toggle="panelrefresh"><i class="fa fa-refresh"></i></button>
                            <button class="btn" data-toggle="panelcollapse"><i class="fa fa-angle-up"></i></button>
                            <button class="btn" data-toggle="panelremove" data-parent=".col-md-4"><i class="fa fa-times"></i></button>
                        </div>
                        <!--/ option -->
                    </div>
                </div>
                <div class="panel-collapse pull out">
                    <div class="panel-body">
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
                </div>
            </div>
        </div>
    <?php $count++; ?>
    <?php endforeach; ?>
</div>