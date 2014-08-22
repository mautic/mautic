<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'report');

$header = ($report->getId()) ?
    $view['translator']->trans('mautic.report.report.header.edit',
        array('%name%' => $view['translator']->trans($report->getTitle()))) :
    $view['translator']->trans('mautic.report.report.header.new');

$view['slots']->set("headerTitle", $header);

// Rendered hardcode HTML below is to use as a guide for designing the form and functionality, it'll go away later
?>

<div class="scrollable">
    <?php //echo $view['form']->form($form); ?>
    <form novalidate="" autocomplete="off" data-toggle="ajax" role="form" name="report" method="post" action="/index_dev.php/reporting/edit/1">
        <div id="report">
            <div class="row">
                <div class="form-group col-xs-12 col-sm-8 col-md-6">
                    <label class="control-label required" for="report_title">Report Name</label>
                    <input type="text" id="report_title" name="report[title]" required="required" class="form-control" value="Top Hit Pages"></div>
            </div>
            <div class="row">
                <div class="form-group  col-xs-12">
                    <label class="control-label">Published?</label>

                    <div class="choice-wrapper">
                        <div id="report_isPublished" class="btn-group btn-block" data-toggle="buttons">
                            <label class="btn btn-success">
                                <input type="radio" id="report_isPublished_0" name="report[isPublished]" value="0"> No </label>
                            <label class="btn btn-success active">
                                <input type="radio" id="report_isPublished_1" name="report[isPublished]" value="1" checked="checked"> Yes </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group  col-xs-12">
                    <label class="control-label">System Report?</label>

                    <div class="choice-wrapper">
                        <div id="report_isSystem" class="btn-group btn-block" data-toggle="buttons">
                            <label class="btn btn-success">
                                <input type="radio" id="report_isSystem_0" name="report[isSystem]" value="0"> No </label>
                            <label class="btn btn-success active">
                                <input type="radio" id="report_isSystem_1" name="report[isSystem]" value="1" checked="checked"> Yes </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group  col-xs-12 col-sm-8 col-md-6">
                    <label class="control-label" for="report_source">Data Source</label>
                    <span data-toggle="tooltip" data-container="body" data-placement="top" data-original-title="Choose the data source to use for this report">
                        <i class="fa fa-question-circle"></i>
                    </span>

                    <div class="choice-wrapper">
                        <select id="report_source" name="report[source]" class="form-control">
                            <option value="Page" selected="selected">Pages</option>
                            <option value="Lead">Leads</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group  col-xs-12 col-sm-8 col-md-6">
                    <label class="control-label">Columns to Include in Report</label>
                    <div class="row">
                        <div class="choice-wrapper col-xs-5">
                            <select id="report_available_columns" name="report[available_columns]" class="form-control" multiple="multiple" size="5">
                                <option value="id">ID</option>
                                <option value="created_by">Created By</option>
                                <option value="category">Category</option>
                                <option value="is_published">Published</option>
                                <option value="lang">Language</option>
                            </select>
                        </div>
                        <div class="col-xs-2">
                            <div class="text-center" style="margin-bottom: 10px; padding-top: 18px;">
                                <button class="btn btn-sm btn-default"><i class="fa fa-caret-square-o-left"></i></button>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-sm btn-default"><i class="fa fa-caret-square-o-right"></i></button>
                            </div>
                        </div>
                        <div class="choice-wrapper col-xs-5">
                            <select id="report_selected_columns" name="report[selected_columns]" class="form-control" multiple="multiple" size="5">
                                <option value="title">Title</option>
                                <option value="hits">Hits</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group  col-xs-12 col-sm-8 col-md-6">
                    <label class="control-label">Filters</label>
                    <div class="row">
                        <div class="choice-wrapper col-xs-4">
                            <label class="control-label" for="report_filter_rule_1">Column</label>
                            <select id="report_filter_rule_1" name="report[filter_rule_1]" class="form-control">
                                <option value="id">ID</option>
                                <option value="title">Title</option>
                                <option value="created_by">Created By</option>
                                <option value="category">Category</option>
                                <option value="is_published">Published</option>
                                <option value="lang">Language</option>
                                <option value="hits" selected="selected">Hits</option>
                            </select></div>
                        <div class="choice-wrapper col-xs-2">
                            <label class="control-label" for="report_filter_condition_1">Condition</label>
                            <select id="report_filter_condition_1" name="report[filter_condition_1]" class="form-control">
                                <option value="=">=</option>
                                <option value=">">&gt;</option>
                                <option value=">=" selected="selected">&gt;=</option>
                                <option value="<">&lt;</option>
                                <option value="<=">&lt;=</option>
                                <option value="!=">!=</option>
                            </select></div>
                        <div class="choice-wrapper col-xs-4">
                            <div class="form-group">
                                <label class="control-label required" for="report_filter_value_1">Value</label>
                                <input type="text" id="report_filter_value_1" name="report[filter_value_1]" required="required" class="form-control" value="50">
                            </div>
                        </div>
                        <div class="col-xs-2">
                            <label class="control-label">Remove Filter</label>
                            <button class="btn btn-sm btn-danger"><i class="fa fa-minus-square-o"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12">
                    <button class="btn btn-primary">Add Filter</button>
                </div>
            </div>
        </div>
    </form>
    <div class="footer-margin"></div>
</div>
