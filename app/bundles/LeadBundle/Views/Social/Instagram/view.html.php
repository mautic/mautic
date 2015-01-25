<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-toolbar-wrapper">
    <div class="panel-toolbar">
        <ul class="nav nav-tabs nav-justified">
            <li class="active">
                <a href="#InstagramProfile" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.profile'); ?>
                </a>
            </li>
            <li>
                <a href="#InstagramPhotos" role="tab" data-toggle="tab">
                    <?php echo $view['translator']->trans('mautic.lead.lead.social.photos'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
<div class="panel-body tab-content">
    <div class="tab-pane active" id="InstagramProfile">
        <?php echo $view->render('MauticLeadBundle:Social/Instagram:profile.html.php', array(
            'profile' => $details['profile']
        )); ?>
    </div>
    <div class="tab-pane" id="InstagramPhotos">
        <?php echo $view->render('MauticLeadBundle:Social/Instagram:photos.html.php', array(
            'activity' => (!empty($details['activity']['photos'])) ? $details['activity']['photos'] : array()
        )); ?>
    </div>
</div>