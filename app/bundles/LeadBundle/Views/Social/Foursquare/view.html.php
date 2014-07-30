<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-toolbar-wrapper">
    <div class="panel-toolbar">
        <ul class="nav nav-tabs nav-justified">
            <li class="active">
                <a href="#FoursquareMayor" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.foursquare.mayorship'); ?>
                </a>
            </li>
            <li>
                <a href="#FoursquareLists" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.foursquare.lists'); ?>
                </a>
            </li>
            <li>
                <a href="#FoursquareTips" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.foursquare.tips'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="panel-body tab-content">
    <div class="tab-pane active" id="FoursquareMayor">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:mayor.html.php', array(
            'lead'      => $lead,
            // 'profile'   => $details['profile']
        )); ?>
    </div>
    <div class="tab-pane" id="FoursquareLists">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:lists.html.php', array(
            'lead'      => $lead,
            // 'activity'   => $details['activity']
        )); ?>
    </div>
    <div class="tab-pane" id="FoursquareTips">
        <?php echo $view->render('MauticLeadBundle:Social/Foursquare:tips.html.php', array(
            'lead'      => $lead,
            // 'activity'   => $details['activity']
        )); ?>
    </div>
</div>