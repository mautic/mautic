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
                <a href="#TwitterProfile" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.twitter.profile'); ?>
                </a>
            </li>
            <li>
                <a href="#TwitterTweets" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.twitter.tweets'); ?>
                </a>
            </li>
            <li>
                <a href="#TwitterPhotos" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.twitter.photos'); ?>
                </a>
            </li>
            <li>
                <a href="#TwitterTags" role="tab" data-toggle="tab">
                   <?php echo $view['translator']->trans('mautic.lead.lead.social.twitter.tags'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="panel-body tab-content">
    <div class="tab-pane active" id="TwitterProfile">
        <?php echo $view->render('MauticLeadBundle:Social/Twitter:profile.html.php', array(
            'lead'      => $lead,
            'profile'   => $details['profile']
        )); ?>
    </div>
    <div class="tab-pane" id="TwitterTweets">
        <?php echo $view->render('MauticLeadBundle:Social/Twitter:tweets.html.php', array(
            'lead'        => $lead,
            'activity'    => $details['activity']['tweets']
        )); ?>
    </div>
    <div class="tab-pane" id="TwitterPhotos">
        <?php echo $view->render('MauticLeadBundle:Social/Twitter:photos.html.php', array(
            'lead'      => $lead,
             'activity' => $details['activity']['photos']
        )); ?>
    </div>
    <div class="tab-pane" id="TwitterTags">
        <?php echo $view->render('MauticLeadBundle:Social/Twitter:tags.html.php', array(
            'lead'      => $lead,
            'activity' => $details['activity']['tags']
        )); ?>
    </div>
</div>