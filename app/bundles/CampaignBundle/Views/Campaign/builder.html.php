<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
    <div class="hide page-builder">
        <div class="page-builder-content">
           <?php /* <input type="hidden" id="pageBuilderUrl" value="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'builder', 'objectId' => $activePage->getSessionId())); ?>" /> */ ?>
            <div class="row">
                <div class="col-xs-8" id="CampaignCanvas">
                    <div id="source" class="draggable" style="height: 100px; position: absolute; width: 100px; background-color: #000; margin: 10px;"></div>
                    <div id="target" class="draggable" style="height: 100px; position: absolute;  width: 100px; background-color: #000; margin: 10px;"></div>
                </div>
                <div class="col-xs-4" id="CampaignComponents">

                </div>
            </div>
        </div>
        <div class="page-builder-panel">
            <p>
                <button class="btn btn-danger btn-close-builder" onclick="Mautic.closePageEditor();"><?php echo $view['translator']->trans('mautic.page.page.builder.close'); ?></button>
            </p>

            <div><em><?php echo $view['translator']->trans('mautic.page.page.token.help'); ?></em></div>
            <div class="panel-group margin-sm-top" id="page_tokens">
<?php /*
                <?php foreach ($tokens as $k => $t): ?>
                    <?php $id = \Mautic\CoreBundle\Helper\InputHelper::alphanum($k); ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a style="display: block;" data-toggle="collapse" data-parent="#page_tokens" href="#<?php echo $id; ?>">
                                    <span class="pull-left">
                                        <?php echo $t['header']; ?>
                                    </span>
                                    <span class="pull-right">
                                        <i class="fa fa-lg fa-fw fa-angle-down"></i>
                                    </span>
                                    <div class="clearfix"></div>
                                </a>
                            </h4>
                        </div>
                        <div id="<?php echo $id; ?>" class="panel-collapse collapse">
                            <div class="panel-body">
                                <?php echo $t['content']; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
*/ ?>
            </div>
        </div>
    </div>
<?php /*
<div class="modal fade" id="campaignBuilder" tabindex="-1" role="dialog" aria-labelledby="CampaignBuilder-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-8" id="CampaignCanvas">
                        <div id="source" class="draggable" style="height: 100px; position: absolute; width: 100px; background-color: #000; margin: 10px;"></div>
                        <div id="target" class="draggable" style="height: 100px; position: absolute;  width: 100px; background-color: #000; margin: 10px;"></div>
                    </div>
                    <div class="col-xs-4" id="CampaignComponents">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
*/?>
<script>
Mautic.drawCampaign = function() {
    var instance = jsPlumb.getInstance({
        DragOptions : { cursor: 'pointer', zIndex:2000 },
        PaintStyle : { strokeStyle:'#666' },
        EndpointStyle : { width:20, height:16, strokeStyle:'#666' },
        Endpoint : "Rectangle",
        Anchors : ["TopCenter", "TopCenter"],
        Container:"CampaignCanvas"
    });

    // configure some drop options for use by all endpoints.
    var exampleDropOptions = {
        tolerance:"touch",
        hoverClass:"dropHover",
        activeClass:"dragActive"
    };

    var exampleEndpoint = {
        endpoint:"Rectangle",
        paintStyle:{ width:25, height:21, fillStyle:"#00f" },
        isSource:true,
        reattach:true,
        connectorStyle:{ strokeStyle:"#00f", lineWidth:6 },
        connector: ["Bezier", { curviness:63 } ],
        isTarget:true,
        dropOptions : exampleDropOptions
    };

    var e1 = instance.addEndpoint("source", { anchor:"Bottom" }, exampleEndpoint);
    var e2 = instance.addEndpoint("target", { anchor:"Bottom" }, exampleEndpoint);

    instance.draggable(jsPlumb.getSelector("#CampaignCanvas .draggable"));

    jsPlumb.fire("jsPlumbDemoLoaded", instance);
};
</script>