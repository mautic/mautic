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
            <a href="#TwitterProfile" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.profile'); ?>
            </a>
        </li>
        <li>
            <a href="#TwitterTweets" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.twitter.tweets'); ?>
            </a>
        </li>
        <li>
            <a href="#TwitterPhotos" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.photos'); ?>
            </a>
        </li>
        <li>
            <a href="#TwitterTags" role="tab" data-toggle="tab">
               <?php echo $view['translator']->trans('mautic.lead.lead.social.tags'); ?>
            </a>
        </li>
    </ul>
</div>
<div class="np panel-body tab-content">
    <div class="pa-20 tab-pane active" id="TwitterProfile">
        <?php echo $view->render('MauticSocialBundle:Integration/Twitter/Profile:profile.html.php', [
            'lead'    => $lead,
            'profile' => $details['profile'],
        ]); ?>
    </div>
    <div class="tab-pane" id="TwitterTweets">
        <?php echo $view->render('MauticSocialBundle:Integration/Twitter/Profile:tweets.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['tweets'])) ? $details['activity']['tweets'] : [],
        ]); ?>
    </div>
    <div class="pa-20 tab-pane" id="TwitterPhotos">
        <?php echo $view->render('MauticSocialBundle:Integration/Twitter/Profile:photos.html.php', [
            'lead'      => $lead,
             'activity' => (!empty($details['activity']['photos'])) ? $details['activity']['photos'] : [],
        ]); ?>
    </div>
    <div class="pa-20 tab-pane" id="TwitterTags">
        <?php echo $view->render('MauticSocialBundle:Integration/Twitter/Profile:tags.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['tags'])) ? $details['activity']['tags'] : [],
        ]); ?>
    </div>
</div>