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

<div class="panel-toolbar np">
    <ul class="nav nav-tabs pr-md pl-md">
        <li class="active">
            <a href="#FoursquareProfile" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.profile'); ?>
            </a>
        </li>
        <li>
            <a href="#FoursquareTips" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.foursquare.tips'); ?>
            </a>
        </li>
    </ul>
</div>

<div class="np panel-body tab-content">
    <div class="pa-20 tab-pane active" id="FoursquareProfile">
        <?php echo $view->render('MauticSocialBundle:Integration/Foursquare/Profile:profile.html.php', [
            'lead'    => $lead,
            'profile' => $details['profile'],
        ]); ?>
    </div>
    <div class="tab-pane" id="FoursquareTips">
        <?php echo $view->render('MauticSocialBundle:Integration/Foursquare/Profile:tips.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['tips'])) ? $details['activity']['tips'] : [],
        ]); ?>
    </div>
</div>