<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$sms = $event['extra']['sms'];
?>

<li class="wrapper form-submitted">
    <div class="figure"><span class="fa <?php echo isset($icons['sms']) ? $icons['sms'] : '' ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3>
                <a href="<?php echo $view['router']->generate('mautic_sms_action',
                    array("objectAction" => "preview", "objectId" => $sms->getId())); ?>"
                   data-toggle="ajaxmosal" data-target="#MauticSharedModal" data-header="<?php echo $view['translator']->trans('mautic.sms.smses.header.preview'); ?>" data-footer="false">
                    <?php echo $sms->getName(); ?>
                </a>
            </h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>
        <?php if (isset($event['extra'])) : ?>
            <div class="panel-footer">
                <dl class="dl-horizontal">
                    <?php if (isset($link)) : ?>
                        <dt><?php echo $view['translator']->trans('mautic.core.channel'); ?></dt>
                        <dd class="ellipsis"><?php echo $view['translator']->trans('mautic.sms.sms'); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    </div>
</li>
