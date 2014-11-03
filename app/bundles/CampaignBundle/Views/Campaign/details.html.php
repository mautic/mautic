<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');
$view['slots']->set("headerTitle", $entity->getName());
?>
<?php
$view['slots']->start('actions');
if ($permissions['campaign:campaigns:edit']): ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_campaign_action', array("objectAction" => "edit", "objectId" => $entity->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_campaign_index"
       class="btn btn-default">
        <i class="fa fa-fw fa-pencil-square-o"></i><?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
<?php endif; ?>
<?php if ($permissions['campaign:campaigns:delete']): ?><a href="javascript:void(0);"
   onclick="Mautic.showConfirmation(
       '<?php echo $view->escape($view["translator"]->trans("mautic.campaign.confirmdelete",
       array("%name%" => $entity->getName() . " (" . $entity->getId() . ")")), 'js'); ?>',
       '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.delete"), 'js'); ?>',
       'executeAction',
       ['<?php echo $view['router']->generate('mautic_campaign_action',
       array("objectAction" => "delete", "objectId" => $entity->getId())); ?>',
       '#mautic_campaign_index'],
       '<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);"
    class="btn btn-default">
    <span><i class="fa fa-fw fa-trash-o"></i><?php echo $view['translator']->trans('mautic.core.form.delete'); ?></span>
</a>
<?php endif; ?>
<?php $view['slots']->stop(); ?>

<?php //@todo - output campaign details/actions ?>
<!--@todo - output campaign details/actions-->

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- campaign detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <h4 class="fw-sb">Super Awesome Campaign</h4>
                        <p class="text-white dark-sm mb-0">Created on 7 Jan 2014</p>
                    </div>
                    <div class="col-xs-6 va-m text-right">
                        <h4 class="fw-sb"><span class="label label-success">PUBLISH UP</span></h4>
                    </div>
                </div>
            </div>
            <!--/ campaign detail header -->

            <!-- campaign detail collapseable -->
            <div class="collapse" id="campaign-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <tr>
                                    <td width="20%"><span class="fw-b">Description</span></td>
                                    <td>Campaign description Lorem ipsum dolor sit amet, consectetur adipisicing.</td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Created By</span></td>
                                    <td>Dan Counsell</td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Category</span></td>
                                    <td>Some category</td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Publish Up</span></td>
                                    <td>Mar 30, 2014</td>
                                </tr>
                                <tr>
                                    <td width="20%"><span class="fw-b">Publish Down</span></td>
                                    <td>Apr 10, 2014</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ campaign detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- campaign detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow" data-toggle="collapse" data-target="#campaign-details"><span class="caret"></span></a>
                </span>
            </div>
            <!--/ campaign detail collapseable toggler -->

            <!--
            some stats: need more input on what type of campaign data to show.
            delete if it is not require
            -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">Asset Download</h5>
                                    <h2 class="fw-b">285</h2>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-cloud-download"></span></h3>
                                </div>
                            </div>
                            <div class="plugin-sparkline text-right pr-md pl-md"
                            sparkHeight="34"
                            sparkWidth="180"
                            sparkType="bar"
                            sparkBarWidth="8"
                            sparkBarSpacing="3"
                            sparkZeroAxis="false"
                            sparkBarColor="#00B49C">
                                129,137,186,167,200,115,118,162,112,106,104,106
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">Campaign View</h5>
                                    <h2 class="fw-b">132</h2>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-eye"></span></h3>
                                </div>
                            </div>
                            <div class="plugin-sparkline text-right pr-md pl-md"
                            sparkHeight="34"
                            sparkWidth="180"
                            sparkType="bar"
                            sparkBarWidth="8"
                            sparkBarSpacing="3"
                            sparkZeroAxis="false"
                            sparkBarColor="#F86B4F">
                                156,162,185,102,144,156,150,114,198,117,120,138
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel ovf-h bg-auto bg-light-xs">
                            <div class="panel-body box-layout">
                                <div class="col-xs-8 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">Page Hit</h5>
                                    <h2 class="fw-b">185</h2>
                                </div>
                                <div class="col-xs-4 va-t text-right">
                                    <h3 class="text-white dark-sm"><span class="fa fa-thumb-tack"></span></h3>
                                </div>
                            </div>
                            <div class="plugin-sparkline text-right pr-md pl-md"
                            sparkHeight="34"
                            sparkWidth="180"
                            sparkType="bar"
                            sparkBarWidth="8"
                            sparkBarSpacing="3"
                            sparkZeroAxis="false"
                            sparkBarColor="#FDB933">
                                115,195,185,110,182,192,168,185,138,176,119,109
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ some stats -->

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active"><a href="#event-container" role="tab" data-toggle="tab">Event Lists</a></li>
                <li class=""><a href="#lead-container" role="tab" data-toggle="tab">Lead Lists</a></li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #events-container -->
            <div class="tab-pane active fade in bdr-w-0" id="event-container">
                <!-- header -->
                <div class="mb-lg">
                    <!-- form -->
                    <form action="" class="panel mb-0">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Filter event...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->
                </div>

                <!-- start: trigger type event -->
                <ul class="list-group">
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-bullseye text-danger"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #1</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">asset.download</em>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-bullseye text-danger"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #2</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">campaign.leadchange</em>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-bullseye text-danger"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #3</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">page.pagehit</em>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-bullseye text-danger"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #4</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">email.open</em>
                            </div>
                        </div>
                    </li>
                </ul>
                <!--/ end: trigger type event -->

                <!-- start: action type event -->
                <ul class="list-group mb-0">
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-rocket text-success"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #1</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">campaign.addremovelead</em>
                            </div>
                        </div>
                    </li>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa fa-rocket text-success"></span></h3>
                            </div>
                            <div class="col-md-7 va-m">
                                <h5 class="fw-sb text-primary mb-xs">Campaign Event Name #2</h5>
                                <h6 class="text-white dark-sm">Event description lorem ipsum dolor sit amet</h6>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <em class="text-white dark-sm">campaign.addremovelead</em>
                            </div>
                        </div>
                    </li>
                </ul>
                <!--/ end: action type event -->
            </div>
            <!--/ #events-container -->

            <!-- #lead-container -->
            <div class="tab-pane fade bdr-w-0" id="lead-container">
                <!-- header -->
                <div class="box-layout mb-lg">
                    <!-- form -->
                    <form action="" class="panel col-xs-8 va-m">
                        <div class="form-control-icon pa-xs">
                            <input type="text" class="form-control bdr-w-0" placeholder="Filter leads...">
                            <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                        </div>
                    </form>
                    <!--/ form -->

                    <!-- button -->
                    <div class="col-xs-4 va-m text-right">
                        <div class="btn-group">
                            <a href="javascript:void(0)" class="btn btn-default active"><span class="fa fa-th-large"></span></a>
                            <a href="javascript:void(0)" class="btn btn-default"><span class="fa fa-th-list"></span></a>
                        </div>
                    </div>
                </div>
                <!--/ header -->

                <ul class="row list-unstyled">
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/_shahedk/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Castor Acosta</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">dolor@lacus.com</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/chadengle/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Asher Peterson</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">consectetuer@justoProinnon.net</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/stylecampaign/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Tanner Logan</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">nulla@purusin.net</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/chexee/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Violet Dorsey</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">eleifend@fermentumrisus.edu</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/emieljanson/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Bruce Frederick</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">nisi@at.net</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/jayrobinson/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Igor Gonzales</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">mi.lorem@leoCras.ca</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/walterstephanie/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Bethany Tyler</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">sit.amet.diam@dui.com</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/liang/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Tamara Mcmillan</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">blandit.viverra@turpis.co.uk</p>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="col-sm-4 mb-md">
                        <div class="list-group mb-0">
                            <a href="javascript:void(0)" class="list-group-item box-layout">
                                <span class="col-xs-3 va-m">
                                    <span class="pull-left img-wrapper img-rounded">
                                        <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/yalozhkin/128.jpg" style="width:42px">
                                    </span>
                                </span>
                                <span class="col-xs-9 va-m">
                                    <h5 class="media-body fw-sb mb-2">Cody Delacruz</h5>
                                    <p class="text-white dark-sm mb-0 ellipsis">sagittis.felis@Nulla.com</p>
                                </span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <!--/ #lead-container -->
        </div>
        <!--/ end: tab-content -->
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- recent activity -->
        <?php echo $view->render('MauticCoreBundle:Default:recentactivity.html.php', array('logs' => $logs)); ?>

    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->