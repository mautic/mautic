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
    <ul class="nav nav-tabs nav-justified pr-md pl-md">
        <li class="active">
            <a href="#GoogleProfile" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.profile'); ?>
            </a>
        </li>
        <li>
            <a href="#GooglePosts" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.posts'); ?>
            </a>
        </li>
        <li>
            <a href="#GooglePhotos" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.photos'); ?>
            </a>
        </li>
        <li>
            <a href="#GoogleTags" role="tab" data-toggle="tab">
                <?php echo $view['translator']->trans('mautic.lead.lead.social.tags'); ?>
            </a>
        </li>
    </ul>
</div>
<div class="panel-body tab-content">
    <div class="tab-pane active" id="GoogleProfile">
        <?php echo $view->render('MauticSocialBundle:Integration/GooglePlus/Profile:profile.html.php', [
            'lead'    => $lead,
            'profile' => $details['profile'],
        ]); ?>
    </div>
    <div class="tab-pane" id="GooglePosts">
        <?php echo $view->render('MauticSocialBundle:Integration/GooglePlus/Profile:posts.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['posts'])) ? $details['activity']['posts'] : [],
        ]); ?>
    </div>
    <div class="tab-pane" id="GooglePhotos">
        <?php echo $view->render('MauticSocialBundle:Integration/GooglePlus/Profile:photos.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['photos'])) ? $details['activity']['photos'] : [],
        ]); ?>
    </div>
    <div class="tab-pane" id="GoogleTags">
        <?php echo $view->render('MauticSocialBundle:Integration/GooglePlus/Profile:tags.html.php', [
            'lead'     => $lead,
            'activity' => (!empty($details['activity']['tags'])) ? $details['activity']['tags'] : [],
        ]); ?>
    </div>
</div>