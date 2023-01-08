<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');

$header = ($report->getId()) ?
    $view['translator']->trans(
        'mautic.report.report.header.edit',
        ['%name%' => $view['translator']->trans($report->getName())]
    ) :
    $view['translator']->trans('mautic.report.report.header.new');

$view['slots']->set('headerTitle', $header);
$showGraphTab = count($form['graphs']->vars['choices']);

$scheduleTabErrorClass = ($view['form']->containsErrors($form['toAddress'])) ? 'class="text-danger"' : '';
?>

<?php echo $view['form']->start($form); ?>
<?php echo $view['form']->errors($form, true); ?>
    <div class="box-layout">
        <div class="col-md-9 bg-white height-auto">
            <div class="row">
                <div class="col-xs-12">
                    <!-- tabs controls -->
                    <ul class="bg-auto nav nav-tabs pr-md pl-md">
                        <li class="active">
                            <a href="#details-container" role="tab"
                               data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                        </li>
                        <li class="">
                            <a href="#data-container" role="tab"
                               data-toggle="tab"><?php echo $view['translator']->trans('mautic.report.tab.data'); ?></a>
                        </li>
                        <li class="<?php if (!$showGraphTab): echo 'hide'; endif; ?>" id="graphs-tab">
                            <a href="#graphs-container" role="tab"
                               data-toggle="tab"><?php echo $view['translator']->trans(
                                    'mautic.report.tab.graphs'
                                ); ?></a>
                        </li>
                        <li>
                            <a href="#schedule-container" role="tab" <?php echo $scheduleTabErrorClass; ?>
                               data-toggle="tab"><?php echo $view['translator']->trans('mautic.report.tab.schedule'); ?></a>
                        </li>
                    </ul>
                    <!--/ tabs controls -->

                    <div class="tab-content pa-md">
                        <div class="tab-pane fade in active bdr-w-0" id="details-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <?php echo $view['form']->row($form['name']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <?php echo $view['form']->row($form['source']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="pa-md">
                                        <?php echo $view['form']->row($form['description']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade bdr-w-0" id="data-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <h4><strong><?php echo $view['translator']->trans(
                                                    'mautic.report.report.form.columnselector'
                                                ); ?></strong></h4>
                                        <?php echo $view['form']->row($form['columns']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ml-md">
                                        <h4><strong><?php echo $view['translator']->trans(
                                                    'mautic.core.order'
                                                ); ?></strong></h4>
                                        <?php echo $view['form']->row($form['tableOrder']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="pa-md">
                                        <h4><strong><?php echo $view['translator']->trans(
                                                    'mautic.core.filters'
                                                ); ?></strong></h4>
                                        <?php echo $view['form']->row($form['filters']); ?>
                                    </div>
                                    <div class="hide">
                                        <div id="filterValueYesNoTemplate">
                                            <?php echo $view['form']->widget($form['value_template_yesno']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <h4><strong><?php echo $view['translator']->trans(
                                                    'mautic.report.form.groupby'
                                                ); ?></strong></h4>
                                        <?php echo $view['form']->row($form['groupBy']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <h4><strong><?php echo $view['translator']->trans(
                                                    'mautic.core.calculated.fields'
                                                ); ?></strong></h4>
                                        <?php echo $view['form']->row($form['aggregators']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade bdr-w-0<?php if (!$showGraphTab): echo 'hide'; endif; ?>"
                             id="graphs-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="pa-md">
                                        <?php echo $view['form']->row(
                                            $form->vars['form']->children['settings']['showGraphsAboveTable']
                                        ); ?>
                                        <?php echo $view['form']->row($form['graphs']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade bdr-w-0" id="schedule-container">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php echo $view['form']->row($form['isScheduled']); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 col-md-6">
                                    <div class="schedule_form">
                                        <?php echo $view['form']->row($form['toAddress']); ?>
                                        <?php echo $view['form']->row($form['scheduleUnit']); ?>

                                        <div id='scheduleMonthFrequency''>
                                            <?php echo $view['form']->row($form['scheduleMonthFrequency']); ?>
                                        </div>
                                        <div id='scheduleDay'>
                                            <?php echo $view['form']->row($form['scheduleDay']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <div class="schedule_form well well-sm mt-lg">
                                        <span id="schedule_preview_url" data-url="<?php echo $view['router']->path('mautic_report_schedule_preview'); ?>"></span>
                                        <div id="schedule_preview_data">
                                            <strong><?php echo $view['translator']->trans('mautic.report.schedule.preview_data'); ?></strong>
                                            <div id="schedule_preview_data_content" class="mt-sm"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 bg-white height-auto bdr-l">
            <div class="pr-lg pl-lg pt-md pb-md">
                <?php echo $view['form']->row($form['isPublished']); ?>
                <?php echo $view['form']->row($form['system']); ?>
                <?php echo $view['form']->row($form['createdBy']); ?>
                <hr>
                <h5><?php echo $view['translator']->trans(
                        'mautic.report.report.form.display.dynamic.filters.settings'
                    ); ?></h5>
                <br>
                <?php echo $view['form']->row($form->vars['form']->children['settings']['showDynamicFilters']); ?>
                <?php echo $view['form']->row($form->vars['form']->children['settings']['hideDateRangeFilter']); ?>
            </div>
        </div>
    </div>
<?php echo $view['form']->end($form); ?>
