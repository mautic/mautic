<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.dashboard.header.index'));
$view['slots']->set('mauticContent', 'dashboard');

$cols = 0;
if ( $popularPages )     $cols++;
if ( $popularAssets )    $cols++;
if ( $popularCampaigns ) $cols++;
if ($cols == 0) $cols = 1;
$colspan = 12/$cols;

?>
<div class="box-layout">
    <div class="np col-md-9 height-auto bg-white">
    	<div class="bg-auto bg-dark-xs">
    		<div class="pa-md mb-lg">
    			<div class="row">
    			    <div class="col-md-4">
    			        <div class="panel mb-0">
                            <div class="text-center doughnut-wrapper">
                                <canvas id="return-rate" width="110" height="110" data-visit-count="<?php echo array_sum($newReturningVisitors) ?>" data-return-count="<?php echo $newReturningVisitors['returning'] ?>"></canvas>
        			            <div class="doughnut-inner-text doughnut-return-rate">
                                <?php echo $view['translator']->trans('mautic.dashboard.label.return.rate'); ?>
                                <br><?php echo $returnRate ?>%
                                </div>
    			            </div>
                            <ul class="list-group">
    			                <li class="list-group-item">
                                    <?php echo $view['translator']->trans('mautic.dashboard.label.unique.visitors'); ?>
                                    <span class="badge pull-right"><?php echo $newReturningVisitors['unique']; ?></span>
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
                                <canvas id="click-rate" width="110" height="110" data-sent-count="<?php echo $sentReadCount['sent_count'] ?>" data-click-count="<?php echo $clickthroughCount ?>"></canvas>
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
                                    <span class="badge pull-right"><?php echo $clickthroughCount ?></span>
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
                        <?php echo $view['translator']->trans('mautic.dashboard.label.upcoming.emails'); ?>
                    </a>
                </li>
    	    </ul>
    	</div>
        <div class="tab-content pa-md">
            <!-- #email-stats-container -->
            <div class="tab-pane active fade in bdr-w-0" id="email-stats-container">
                <div class="row">
                    <?php if ($popularPages) : ?>
                        <div class="col-md-<?php echo $colspan; ?>">
                            <div class="panel panel-default bdr-t-wdh-0">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.pages'); ?>
                                    </h3>
                                </div>
                                <div class="table-responsive page-list">
                                    <table class="table table-hover table-striped table-bordered point-list">
                                        <thead>
                                            <tr>
                                                <th><?php echo $view['translator']->trans('mautic.dashboard.label.title'); ?></th>
                                                <th><?php echo $view['translator']->trans('mautic.dashboard.label.hits'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($popularPages as $page) : ?>
                                            <tr>
                                                <td>
                                                <?php if ($page['page_id']) : ?>
                                                    <a href="<?php echo $view['router']->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['page_id'])); ?>" data-toggle="ajax">
                                                        <?php echo $page['title']; ?>
                                                    </a>
                                                <?php else : ?>
                                                    <a href="<?php echo $page['url']; ?>" title="<?php echo $page['url']; ?>">
                                                        <?php $pageUrl = str_replace(array('http://', 'https://'), '', $page['url']); ?>
                                                        <?php echo $view['assets']->shortenText($pageUrl, 30); ?>
                                                    </a>
                                                <?php endif; ?>
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
                        </div>
                    <?php endif; ?>
                    <?php if ($popularAssets) : ?>
                        <div class="col-md-<?php echo $colspan; ?>">
                            <div class="panel panel-default bdr-t-wdh-0">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.assets'); ?>
                                    </h3>
                                </div>
                                <div class="table-responsive">
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
                                                <a href="<?php echo $view['router']->generate('mautic_asset_action', array('objectAction' => 'view', 'objectId' => $asset['id'])); ?>" data-toggle="ajax" title="<?php echo $asset['title'] ?>">
                                                    <?php echo $view['assets']->shortenText($asset['title'], 30); ?>
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
                        </div>
                    <?php endif; ?>
                    <?php if ($popularCampaigns) : ?>
                        <div class="col-md-<?php echo $colspan; ?>">
                            <div class="panel panel-default bdr-t-wdh-0">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <?php echo $view['translator']->trans('mautic.dashboard.label.most.popular.campaigns'); ?>
                                    </h3>
                                </div>
                                <div class="table-responsive">
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
                                                        <a href="<?php echo $view['router']->generate('mautic_campaign_action', array('objectAction' => 'view', 'objectId' => $campaign['campaign_id'])); ?>" data-toggle="ajax" title="<?php echo $campaign['name'] ?>">
                                                            <?php echo $view['assets']->shortenText($campaign['name'], 30); ?>
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!--/ #email-stats-container -->

            <!-- #page-stats-container -->
            <div class="tab-pane fade bdr-w-0" id="page-stats-container">

                <?php if ($upcomingEmails) : ?>
                <ul class="list-group mb-0">
                    <?php foreach ($upcomingEmails as $email): ?>
                    <li class="list-group-item bg-auto bg-light-xs">
                        <div class="box-layout">
                            <div class="col-md-1 va-m">
                                <h3><span class="fa <?php echo isset($icons['email']) ? $icons['email'] : ''; ?> fw-sb text-success"></span></h3>
                            </div>
                            <div class="col-md-4 va-m">
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $view['router']->generate('mautic_campaign_action', array('objectAction' => 'view', 'objectId' => $email['campaign_id'])); ?>" data-toggle="ajax">
                                        <?php echo $email['campaign_name']; ?>
                                    </a>
                                </h5>
                                <span class="text-white dark-sm"><?php echo $email['event_name']; ?></span>
                            </div>
                            <div class="col-md-4 va-m text-right">
                                <a class="btn btn-sm btn-success"  href="<?php echo $view['router']->generate('mautic_lead_action', array('objectAction' => 'view', 'objectId' => $email['lead_id'])); ?>" data-toggle="ajax">
                                    <span class="fa <?php echo isset($icons['lead']) ? $icons['lead'] : ''; ?>"></span>
                                    <?php echo $email['lead']->getName(); ?>
                                </a>
                            </div>
                            <div class="col-md-3 va-m text-right">
                                <?php echo $view['date']->toFull($email['triggerDate']); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo $view['translator']->trans('mautic.note.no.upcoming.emails'); ?>
                    </div>
                <?php endif; ?>
            </div>
            <!--/ #page-stats-container -->
        </div>
    </div>
    <div class="col-md-3 bg-white bdr-l height-auto">

        <!-- activity feed -->
        <?php echo $view->render('MauticDashboardBundle:Default:recentactivity.html.php', array('logs' => $logs, 'icons' => $icons)); ?>

    </div>
</div>
