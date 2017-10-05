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
<div class="hide builder campaign-builder">
    <button type="button" class="btn btn-primary btn-close-campaign-builder" onclick="Mautic.closeCampaignBuilder();">
        <?php echo $view['translator']->trans('mautic.core.close.builder'); ?>
    </button>
    <button type="button" class="btn btn-primary btn-apply-builder" onclick="Mautic.saveCampaignFromBuilder();">
        <?php echo $view['translator']->trans('mautic.core.form.apply'); ?>
    </button>
    <div id="builder-errors" class="alert alert-danger" role="alert" style="display: none;">test</div>
    <div class="builder-content">
        <div id="CampaignCanvas">
            <div id="CampaignEvent_newsource<?php if (!empty($campaignSources)) {
    echo '_hide';
} ?>" class="text-center list-campaign-source list-campaign-leadsource">
                <div class="campaign-event-content">
                    <div>
                        <span class="campaign-event-name ellipsis">
                            <i class="mr-sm fa fa-users"></i> <?php echo $view['translator']->trans('mautic.campaign.add_new_source'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php
            foreach ($campaignSources as $source):
                echo $view->render('MauticCampaignBundle:Source:index.html.php', $source);
            endforeach;

            foreach ($campaignEvents as $event):
                echo $view->render('MauticCampaignBundle:Event:generic.html.php', ['event' => $event, 'campaignId' => $campaignId]);
            endforeach;

            echo $view->render('MauticCampaignBundle:Campaign\Builder:index.html.php',
                [
                    'campaignSources' => $campaignSources,
                    'eventSettings'   => $eventSettings,
                    'campaignId'      => $campaignId,
                ]
            );
            ?>

        </div>
    </div>
</div>
<!-- dropped coordinates -->
<input type="hidden" value="" id="droppedX"/>
<input type="hidden" value="" id="droppedY"/>
<input type="hidden" value="<?php echo $campaignId; ?>" id="campaignId"/>

<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    [
        'id'            => 'CampaignEventModal',
        'header'        => false,
        'footerButtons' => true,
        'dismissible'   => false,
    ]
);

?>
<script>
    <?php if (!empty($canvasSettings)): ?>
    Mautic.campaignBuilderCanvasSettings =
        <?php echo json_encode($canvasSettings, JSON_PRETTY_PRINT); ?>;
    Mautic.campaignBuilderCanvasSources =
        <?php echo json_encode($campaignSources, JSON_PRETTY_PRINT); ?>;
    Mautic.campaignBuilderCanvasEvents =
        <?php echo json_encode($campaignEvents, JSON_PRETTY_PRINT); ?>;
    <?php endif; ?>

    Mautic.campaignBuilderConnectionRestrictions =
        <?php echo json_encode($eventSettings['connectionRestrictions'], JSON_PRETTY_PRINT); ?>;
</script>