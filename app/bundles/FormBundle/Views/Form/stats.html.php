<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo generate stats for results
?>
<!-- left section -->
<div class="col-md-9 bg-white height-auto">
    <div class="bg-auto">
        <!-- form detail header -->
        <div class="pr-md pl-md pt-lg pb-lg">
            <div class="box-layout">
                <div class="col-xs-6 va-m">
                    <h4 class="fw-sb">Super Awesome Form</h4>
                    <p class="text-white dark-sm mb-0">Created on 8 Aug 2014</p>
                </div>
                <div class="col-xs-6 va-m text-right">
                    <h4 class="fw-sb"><span class="label label-success">PUBLISH UP</span></h4>
                </div>
            </div>
        </div>
        <!--/ form detail header -->

        <!-- form detail collapseable -->
        <div class="collapse" id="form-details">
            <div class="pr-md pl-md pb-md">
                <div class="panel shd-none mb-0">
                    <table class="table table-bordered table-striped mb-0">
                        <tbody>
                            <tr>
                                <td width="20%"><span class="fw-b">Description</span></td>
                                <td>Form description Lorem ipsum dolor sit amet, consectetur adipisicing.</td>
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
        <!--/ form detail collapseable -->
    </div>

    <div class="bg-auto bg-dark-xs">
        <!-- form detail collapseable toggler -->
        <div class="hr-expand nm">
            <span data-toggle="tooltip" title="Detail">
                <a href="javascript:void(0)" class="arrow" data-toggle="collapse" data-target="#form-details"><span class="caret"></span></a>
            </span>
        </div>
        <!--/ form detail collapseable toggler -->

        <!-- 
        some stats: need more input on what type of form data to show.
        delete if it is not require
        -->
        <div class="pa-md">
            <div class="row">
                <div class="col-md-4">
                    <div class="panel ovf-h bg-auto bg-light-xs">
                        <div class="panel-body box-layout">
                            <div class="col-xs-8 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">Form Views</h5>
                                <h2 class="fw-b">112</h2>
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
                        sparkBarColor="#00B49C">
                            129,137,186,167,200,115,118,162,112,106,104,106
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel ovf-h bg-auto bg-light-xs">
                        <div class="panel-body box-layout">
                            <div class="col-xs-8 va-m">
                                <h5 class="text-white dark-md fw-sb mb-xs">Form Conversions</h5>
                                <h2 class="fw-b">162</h2>
                            </div>
                            <div class="col-xs-4 va-t text-right">
                                <h3 class="text-white dark-sm"><span class="fa fa-arrows-h"></span></h3>
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
                                <h5 class="text-white dark-md fw-sb mb-xs">Total Leads</h5>
                                <h2 class="fw-b">192</h2>
                            </div>
                            <div class="col-xs-4 va-t text-right">
                                <h3 class="text-white dark-sm"><span class="fa fa-user"></span></h3>
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
            <li class="active"><a href="#actions-container" role="tab" data-toggle="tab">Actions</a></li>
            <li class=""><a href="#fields-container" role="tab" data-toggle="tab">Fields</a></li>
        </ul>
        <!--/ tabs controls -->
    </div>

    <!-- start: tab-content -->
    <div class="tab-content pa-md">
        <!-- #actions-container -->
        <div class="tab-pane active fade in bdr-w-0" id="actions-container">
            <!-- header -->
            <div class="mb-lg">
                <!-- form -->
                <form action="" class="panel mb-0">
                    <div class="form-control-icon pa-xs">
                        <input type="text" class="form-control bdr-w-0" placeholder="Filter actions...">
                        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                    </div>
                </form>
                <!--/ form -->
            </div>
            <!--/ header -->

            <h5 class="fw-sb mb-xs">Leads</h5>
            <ul class="list-group">
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-user text-white dark-xs"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Action Name #1</h5>
                            <h6 class="text-white dark-sm">Action description lorem ipsum dolor sit amet</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">lead.create</em>
                        </div>
                    </div>
                </li>
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-user text-white dark-xs"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Action Name #2</h5>
                            <h6 class="text-white dark-sm">Action description lorem ipsum dolor sit amet</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">lead.points</em>
                        </div>
                    </div>
                </li>
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-user text-white dark-xs"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Action Name #3</h5>
                            <h6 class="text-white dark-sm">Action description lorem ipsum dolor sit amet</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">lead.change</em>
                        </div>
                    </div>
                </li>
            </ul>

            <h5 class="fw-sb mb-xs">Assets</h5>
            <ul class="list-group mb-0">
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-cloud-download text-white dark-xs"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Action Name #1</h5>
                            <h6 class="text-white dark-sm">Action description lorem ipsum dolor sit amet</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">assets.download</em>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <!--/ #actions-container -->

        <!-- #fields-container -->
        <div class="tab-pane fade bdr-w-0" id="fields-container">
            <!-- header -->
            <div class="mb-lg">
                <!-- form -->
                <form action="" class="panel mb-0">
                    <div class="form-control-icon pa-xs">
                        <input type="text" class="form-control bdr-w-0" placeholder="Filter fields...">
                        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
                    </div>
                </form>
                <!--/ form -->
            </div>
            <!--/ header -->

            <h5 class="fw-sb mb-xs">Form Field</h5>
            <ul class="list-group mb-xs">
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-check text-white dark-xs" data-toggle="tooltip" data-placement="left" title="Require"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Field label #1</h5>
                            <h6 class="text-white dark-md">Field type: text</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">field_order: #1</em>
                        </div>
                    </div>
                </li>
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-check text-white dark-xs" data-toggle="tooltip" data-placement="left" title="Require"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Field label #2</h5>
                            <h6 class="text-white dark-md">Field type: email</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">field_order: #2</em>
                        </div>
                    </div>
                </li>
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-check text-white dark-xs" data-toggle="tooltip" data-placement="left" title="Require"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Field label #3</h5>
                            <h6 class="text-white dark-md">Field type: number</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">field_order: #3</em>
                        </div>
                    </div>
                </li>
                <li class="list-group-item bg-auto bg-light-xs">
                    <div class="box-layout">
                        <div class="col-md-1 va-m">
                            <h3><span class="fa fa-times text-white dark-xs" data-toggle="tooltip" data-placement="left" title="Not Require"></span></h3>
                        </div>
                        <div class="col-md-7 va-m">
                            <h5 class="fw-sb text-primary mb-xs">Field label #4</h5>
                            <h6 class="text-white dark-md">Field type: button</h6>
                        </div>
                        <div class="col-md-4 va-m text-right">
                            <em class="text-white dark-sm">field_order: #4</em>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <!--/ #fields-container -->
    </div>
    <!--/ end: tab-content -->
</div>
<!--/ left section -->

<?php
    echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'form-preview',
        'header' => $view['translator']->trans('mautic.form.form.header.preview'),
        'body'   => $view->render('MauticFormBundle:Form:preview.html.php', array('form' => $form)),
        'size'   => 'lg'
    ));
?>