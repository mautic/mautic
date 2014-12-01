<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');

$header = ($report->getId()) ?
    $view['translator']->trans('mautic.report.report.header.edit',
        array('%name%' => $view['translator']->trans($report->getTitle()))) :
    $view['translator']->trans('mautic.report.report.header.new');

$view['slots']->set("headerTitle", $header);
?>

<div class="row">
    <div class="col-xs-12">
        <!-- tabs controls -->
        <ul class="nav nav-tabs pr-md pl-md">
            <li class="active">
            	<a href="#details-container" role="tab" data-toggle="tab">
            		<?php echo $view['translator']->trans('mautic.report.tab.details'); ?>
            	</a>
            </li>
            <li class="">
            	<a href="#filters-container" role="tab" data-toggle="tab">
            		<?php echo $view['translator']->trans('mautic.report.tab.filters'); ?>
            	</a>
            </li>
        </ul>
        <!--/ tabs controls -->

		<?php echo $view['form']->start($form); ?>

		<div class="tab-content pa-md bg-white">
            <div class="tab-pane fade in active bdr-w-0" id="details-container">
                <!-- start: box layout -->
				<div class="box-layout">
				    <!-- container -->
				    <div class="col-md-9 bg-auto height-auto bdr-r">
						<div class="row">
                            <div class="col-md-6">
                                <div class="pa-md">
									<?php echo $view['form']->row($form['title']); ?>
								</div>
							</div>
							<div class="col-md-6">
                                <div class="pa-md">
									<?php echo $view['form']->row($form['source']); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-3 bg-white height-auto">
						<div class="pr-lg pl-lg pt-md pb-md">
							<?php echo $view['form']->row($form['isPublished']); ?>
							<?php echo $view['form']->row($form['system']); ?>
						</div>
					</div>
				</div>
			</div>

            <div class="tab-pane fade bdr-w-0" id="filters-container">
			    <!-- start: box layout -->
				<div class="box-layout">
				    <!-- container -->
				    <div class="col-md-9 bg-auto height-auto bdr-r">
						<div class="row">
                            <div class="col-md-6">
                                <div class="pa-md">
									<?php echo $view['form']->row($form['columns']); ?>
								</div>
							</div>
							<div class="col-md-6">
                                <div class="pa-md">
									<?php echo $view['form']->row($form['filters']); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php echo $view['form']->end($form); ?>
    </div>
</div>
