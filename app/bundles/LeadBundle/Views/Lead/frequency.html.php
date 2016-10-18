<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$leadId   = $lead->getId();
$leadName = $lead->getPrimaryIdentifier();
?>

<?php echo $view['form']->start($form); ?>
<div class="row">
    <div class="col-md-6"><label class="control-label"><?php echo $view['translator']->trans('mautic.lead.preferred.channels'); ?></label>
<ul class="channel-group">
    <?php foreach ($channels as $channel): ?>
        <?php
        $isPreferred = isset($leadChannels[$channel]);
        $switch      = $isPreferred ? 'fa-toggle-on' : 'fa-toggle-off';
        $bgClass     = $isPreferred ? 'text-success' : 'text-danger';
        ?>
        <li class="list-group-item">
            <i class="fa fa-lg fa-fw <?php echo $switch.' '.$bgClass; ?>" id="<?php echo $channel; ?>" onclick="Mautic.togglePreferredChannel(<?php echo $leadId; ?>, '<?php echo $channel; ?>');"></i>
            <span><?php echo $channel; ?></span>
        </li>
    <?php endforeach; ?>
</ul>
    </div>
    <div class="col-md-6"><?php echo $view['form']->row($form['frequency_number']); ?></div>
    <div class="col-md-6"><?php echo $view['form']->row($form['frequency_time']); ?></div>

   <div class="col-md-12">
       <h4 class="modal-title" id="MauticSharedModal-label">Contact Segments</h4> </br>
    <ul class="list-group">
        <?php foreach ($lists as $l): ?>
            <?php
            $inList  = isset($leadLists[$l['id']]);
            $switch  = $inList ? 'fa-toggle-on' : 'fa-toggle-off';
            $bgClass = $inList ? 'text-success' : 'text-danger';
            ?>
            <?php if ($inList): ?>
                <li class="list-group-item">
                    <i class="fa fa-lg fa-fw <?php echo $switch.' '.$bgClass; ?>" id="leadListToggle<?php echo $l['id']; ?>" onclick="Mautic.toggleLeadList('leadListToggle<?php echo $l['id']; ?>', <?php echo $leadId; ?>, <?php echo $l['id']; ?>);"></i>
                    <span><?php echo $l['name'].' ('.$l['alias'].')'; ?></span>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
   </div>
    <?php if (empty($leadLists)): ?>
        <div class="col-md-12">
            <?php echo $lead->getName(); ?> does not belong to any lists, <a data-target='#MauticSharedModal' target="_self" href="<?php echo $view['router']->path(
                'mautic_contact_action',
                ['objectId' => $lead->getId(), 'objectAction' => 'list']); ?>">click here</a> to add this contact to a segment.
        </div>
    <?php endif; ?>
</div>
<?php echo $view['form']->end($form); ?>

