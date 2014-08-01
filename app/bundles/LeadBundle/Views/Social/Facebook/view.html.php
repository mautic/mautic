<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="panel-body">
    <div class="text-right">
        <span class="small">
            <?php echo $view['translator']->trans('mautic.lead.lead.social.lastupdate', array(
                "%datetime%" => $view['date']->toFullConcat($details['lastRefresh'], 'utc')
            )); ?>
        </span>
    </div>
    <?php echo $view->render('MauticLeadBundle:Social/Facebook:profile.html.php', array(
        'lead'      => $lead,
        'profile'   => $details['profile']
    )); ?>
</div>