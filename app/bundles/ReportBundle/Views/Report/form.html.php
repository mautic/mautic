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
    <h1>Live Form</h1>
    <?php echo $view['form']->form($form); ?>
    <h1>Form Mockup</h1>
    <form novalidate="" autocomplete="off" data-toggle="ajax" role="form" name="report" method="post" action="/index_dev.php/reporting/edit/1">
        <div id="report">
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
