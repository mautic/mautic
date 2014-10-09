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
                <a href="#GoogleProfile" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.google.profile'); ?>
                </a>
            </li>
            <li>
                <a href="#GooglePosts" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.google.posts'); ?>
                </a>
            </li>
            <li>
                <a href="#GooglePhotos" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.google.photos'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="panel-body tab-content">
    <div class="tab-pane active" id="GoogleProfile">
        <?php echo $view->render('MauticLeadBundle:Social/GooglePlus:profile.html.php', array(
            'lead'      => $lead,
            // 'profile'   => $details['profile']
        )); ?>
    </div>
    <div class="tab-pane" id="GooglePosts">
        <?php echo $view->render('MauticLeadBundle:Social/GooglePlus:posts.html.php', array(
            'lead'      => $lead,
            // 'activity'   => $details['activity']['posts']
        )); ?>
    </div>
    <div class="tab-pane" id="GooglePhotos">
        <?php echo $view->render('MauticLeadBundle:Social/GooglePlus:photos.html.php', array(
            'lead'      => $lead,
            // 'activity'   => $details['activity']['photos']
        )); ?>
    </div>
</div>