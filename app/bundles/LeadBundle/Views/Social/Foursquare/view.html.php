<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-toolbar np">
    <ul class="nav nav-tabs pr-md pl-md">
        <li class="active">
            <a href="#FoursquareProfile" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.foursquare.profile'); ?>
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
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:profile.html.php', array(
            'lead'      => $lead,
            'profile'   => $details['profile']
        )); ?>
    </div>
    <?php /*
    <div class="tab-pane" id="FoursquareMayor">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:mayor.html.php', array(
            'lead'      => $lead,
            'activity'   => $details['activity']['mayorships']
        )); ?>
    </div>

    <div class="tab-pane" id="FoursquareLists">
    <div class="tab-pane" id="FoursquareLists">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:lists.html.php', array(
            'lead'      => $lead,
            'activity'   => $details['activity']['lists']
        )); ?>
    </div>
    */ ?>
    <div class="tab-pane" id="FoursquareTips">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:tips.html.php', array(
            'lead'      => $lead,
            'activity'   => $details['activity']['tips']
        )); ?>
    </div>
</div>