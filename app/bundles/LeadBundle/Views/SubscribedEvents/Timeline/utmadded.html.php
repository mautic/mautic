<?php
/**
* @package     Mautic
* @copyright   2014 Mautic Contributors. All rights reserved.
* @author      Mautic
* @link        http://mautic.org
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
$utmTags = $event['extra']['utmtags'];

?>

<li class="wrapper">
    <div class="figure"><span class="fa fa-tag"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3>UTM tags</h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>

        <div class="panel-footer">
            <dl class="dl-horizontal">
                <?php if (!empty($utmTags['utmCampaign'])): ?>
                <dt ><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcampaign');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmCampaign']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmContent'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmcontent');?></dt>
                   <dd class="ellipsis"><?php echo $utmTags['utmContent']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmMedium'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmmedium');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmMedium']; ?></dd>
                <?php endif; ?>

                <?php if (!empty($utmTags['utmSource'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.umtsource');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmSource']; ?></dd>
                <?php endif; ?>
                <?php if (!empty($utmTags['utmTerm'])): ?>
                <dt><?php echo $view['translator']->trans('mautic.lead.timeline.event.utmterm');?></dt>
                <dd class="ellipsis"><?php echo $utmTags['utmTerm']; ?></dd>
                <?php endif; ?>
            </dl>

        </div>
    </div>
</li>
