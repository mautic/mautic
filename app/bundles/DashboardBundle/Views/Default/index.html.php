<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", "Dashboard");
$view['slots']->set('mauticContent', 'dashboard');
?>
<div class="box-layout">
    <div class="np col-md-9 height-auto bg-white">
    	<div class="bg-auto bg-dark-xs">
    		<div class="pa-md mb-lg">
    			<div class="row">
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
                            <div class="text-center doughnut-wrapper">
                                <canvas id="open-rate" width="110" height="110" data-sent-count="<?php echo $sentReadCount['sentCount'] ?>" data-read-count="<?php echo $sentReadCount['readCount'] ?>"></canvas>
        			            <div class="doughnut-inner-text doughnut-open-rate">
                                <?php echo $view['translator']->trans('mautic.dashboard.label.open.rate'); ?>
                                <br><?php echo $openRate ?>%
                                </div>
    			            </div>
                            <ul class="list-group">
    			                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.new.visitors'); ?>
                                    <span class="badge pull-right"><?php echo $newReturningVisitors['new']; ?></span>
                                </li>
    			                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.returning.visitors'); ?> 
                                    <span class="badge pull-right"><?php echo $newReturningVisitors['returning']; ?></span>
                                </li>
    			            </ul>
    			        </div>
    			    </div>
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
    			            <div class="text-center doughnut-wrapper">
                                <canvas id="click-rate" width="110" height="110" data-read-count="<?php echo $sentReadCount['readCount'] ?>" data-click-count="<?php echo $sentReadCount['clickCount'] ?>"></canvas>
                                <div class="doughnut-inner-text doughnut-click-rate">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.click.rate'); ?>
                                    <br><?php echo $clickRate ?>%
                                </div>
                            </div>
    			            <ul class="list-group">
                                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.email.delivered'); ?>
                                    <span class="badge pull-right"><?php echo $allSentEmails; ?></span>
                                </li>
                                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.total.click'); ?>
                                    <span class="badge pull-right"><?php echo $sentReadCount['clickCount'] ?></span>
                                </li>
    			            </ul>
    			        </div>
    			    </div>
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
                            <div class="text-center pa-20 jumbo-font h150" id="active-visitors">0</div>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.visits.this.week'); ?>
                                    <span class="badge pull-right"><?php echo $weekVisitors; ?></span>
                                </li>
                                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.visits.all.time'); ?>
                                    <span class="badge pull-right"><?php echo $allTimeVisitors; ?></span>
                                </li>
                            </ul>
    			        </div>
    			    </div>
    			</div>
    		</div>
    		<div class="pa-md mb-lg">
    			<div class="row">
    				<div class="col-sm-12">
    					<div id="dashboard-map"></div>
    				</div>
    			</div>
                <div id="dashboard-map-data" class="hide"><?php echo json_encode($mapData); ?></div>
    		</div>
    		<ul class="nav nav-tabs pr-md pl-md">
    	        <li class="active">
                    <a href="#email-stats-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.dashboard.label.stats'); ?>
                    </a>
                </li>
    	        <li class="">
                    <a href="#page-stats-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.dashboard.label.inbox'); ?>
                    </a>
                </li>
    	    </ul>
    	</div>
        <div class="tab-content pa-md">
            <!-- #email-stats-container -->
            <div class="tab-pane active fade in bdr-w-0" id="email-stats-container">
                <div class="row">
                    <div class="col-md-4">
                            <?php if ($popularPages) : ?>
                            <div class="panel panel-default bdr-t-wdh-0">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.pages'); ?>
                                    </h3>
                                </div>
                                <div class="table-responsive scrollable body-white padding-sm page-list">    
                                    <table class="table table-hover table-striped table-bordered point-list">
                                        <thead>
                                            <tr>
                                                <th><?php echo $view['translator']->trans('mautic.dashboard.label.title'); ?></th>
                                                <th><?php echo $view['translator']->trans('mautic.dashboard.label.lang'); ?></th>
                                                <th><?php echo $view['translator']->trans('mautic.dashboard.label.hits'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($popularPages as $page) : ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['page_id'])); ?>" data-toggle="ajax">
                                                        <?php echo $page['title']; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo $page['lang']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $page['hits']; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <?php if ($popularAssets) : ?>
                            <div class="panel panel-default bdr-t-wdh-0">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.assets'); ?>
                                    </h3>
                                </div>
                                <div class="table-responsive scrollable body-white padding-sm page-list">    
                                <table class="table table-hover table-striped table-bordered point-list">
                                    <thead>
                                        <tr>
                                            <th><?php echo $view['translator']->trans('mautic.dashboard.label.title'); ?></th>
                                            <th><?php echo $view['translator']->trans('mautic.dashboard.label.downloads'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($popularAssets as $asset) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $view['router']->generate('mautic_asset_action', array('objectAction' => 'view', 'objectId' => $asset['id'])); ?>" data-toggle="ajax">
                                                    <?php echo $asset['title']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo $asset['downloadCount']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <?php if ($popularCampaigns) : ?>
                                <div class="panel panel-default bdr-t-wdh-0">
                                    <div class="panel-heading">
                                        <h3 class="panel-title">
                                            <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.campaigns'); ?>
                                        </h3>
                                    </div>
                                    <div class="table-responsive scrollable body-white padding-sm page-list">    
                                        <table class="table table-hover table-striped table-bordered point-list">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $view['translator']->trans('mautic.dashboard.label.title'); ?></th>
                                                    <th><?php echo $view['translator']->trans('mautic.dashboard.label.hits'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($popularCampaigns as $campaign) : ?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo $view['router']->generate('mautic_campaign_action', array('objectAction' => 'view', 'objectId' => $campaign['campaign_id'])); ?>" data-toggle="ajax">
                                                                <?php echo $campaign['name']; ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php echo $campaign['hits']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!--/ #email-stats-container -->

                <!-- #page-stats-container -->
                <div class="tab-pane fade bdr-w-0" id="page-stats-container">
                    <div class="pa-md clearfix">
                        <h6 class="pull-left mr-lg"><span class="fa fa-square text-primary mr-xs"></span> Hit</h6>
                        <h6 class="pull-left mr-lg"><span class="fa fa-square text-warning mr-xs"></span> Conversion</h6>
                        <h6 class="pull-left"><span class="fa fa-square text-success mr-xs"></span> View</h6>
                    </div>
                    <ul class="list-group mb-0">
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014 <span>[current]</span> <span>[parent]</span></a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">5729</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">3426</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">354</a>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Kaleidoscope Conference 2014</a></h5>
                                    <span class="text-white dark-sm">kaleidoscope-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">652</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">115</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">342</a>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-1 va-m">
                                    <h3><span class="fa fa-check-circle-o fw-sb text-success" data-toggle="tooltip" data-placement="right" title="" data-original-title="Published"></span></h3>
                                </div>
                                <div class="col-md-7 va-m">
                                    <h5 class="fw-sb text-primary"><a href="">Copenhagen Conference 2014</a></h5>
                                    <span class="text-white dark-sm">copenhagen-conference-2014</span>
                                </div>
                                <div class="col-md-4 va-m text-right">
                                    <a href="javascript:void(0)" class="btn btn-sm btn-primary">943</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-warning">7598</a>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success">551</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <!--/ #page-stats-container -->
            </div>
    </div>
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- activity feed -->
        <?php echo $view->render('MauticDashboardBundle:Default:recentactivity.html.php', array('logs' => $logs, 'icons' => $icons)); ?>

    </div>
</div>