<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!-- right section -->
<div class="col-md-3 bg-white bdr-l height-auto">
    <!-- form HTML -->
    <div class="pa-md">
        <div class="panel bg-info bg-light-lg bdr-w-0">
            <div class="panel-body">
                <h5 class="fw-sb mb-sm"><?php echo $view['translator']->trans('mautic.form.form.header.copy'); ?></h5>
                <p class="mb-sm"><?php echo $view['translator']->trans('mautic.form.form.help.landingpages'); ?></p>

                <a href="#" class="btn btn-info" data-toggle="modal" data-target="#modal-automatic-copy"><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></a>
                <a href="#" class="btn btn-info" data-toggle="modal" data-target="#modal-manual-copy"><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></a>
            </div>
        </div>
    </div>
    <!--/ form HTML -->

    <!--
    we can leverage data from audit_log table
    and build activity feed from it
    -->
    <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mb-0">
        <div class="panel-heading">
            <div class="panel-title">Recent Activity</div>
        </div>
        <div class="panel-body pt-sm">
            <ul class="media-list media-list-feed">
                <li class="media">
                    <div class="media-object pull-left mt-xs">
                        <span class="figure"></span>
                    </div>
                    <div class="media-body">
                        Dan Counsell Create <strong class="text-primary">Super Awesome Form</strong>
                        <p class="fs-12 text-white dark-sm">Jan 16, 2014</p>
                    </div>
                </li>
                <li class="media">
                    <div class="media-object pull-left mt-xs">
                        <span class="figure"></span>
                    </div>
                    <div class="media-body">
                        Ima Steward Update <strong class="text-primary">Super Awesome Form</strong> action
                        <p class="fs-12 text-white dark-sm">May 1, 2015</p>
                    </div>
                </li>
                <li class="media">
                    <div class="media-object pull-left mt-xs">
                        <span class="figure"></span>
                    </div>
                    <div class="media-body">
                        Ima Steward Update <strong class="text-primary">Super Awesome Form</strong> leads
                        <p class="fs-12 text-white dark-sm">Aug 2, 2014</p>
                    </div>
                </li>
                <li class="media">
                    <div class="media-object pull-left">
                        <span class="figure featured bg-success"><span class="fa fa-check"></span></span>
                    </div>
                    <div class="media-body">
                        Dan Counsell Publish <strong class="text-primary">Super Awesome Form</strong>
                        <p class="fs-12 text-white dark-sm">Sep 23, 2014</p>
                    </div>
                </li>
                <li class="media">
                    <div class="media-object pull-left">
                        <span class="figure"></span>
                    </div>
                    <div class="media-body">
                        Dan Counsell Unpublish <strong class="text-primary">Super Awesome Form</strong>
                        <p class="fs-12 text-white dark-sm">Sep 29, 2014</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
<!--/ right section -->

<!-- #modal-automatic-copy -->
<div class="modal fade" id="modal-automatic-copy">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-sb"><?php echo $view['translator']->trans('mautic.form.form.header.automaticcopy'); ?></h5>
            </div>
            <div class="modal-body">
                <p><?php echo $view['translator']->trans('mautic.form.form.help.automaticcopy'); ?></p>
                <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);">&lt;script type="text/javascript" src="<?php echo $view['router']->generate('mautic_form_generateform', array('id' => $form->getId()), true); ?>"&gt;&lt;/script&gt;</textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!--/ #modal-automatic-copy -->

<!-- #modal-manual-copy -->
<div class="modal fade" id="modal-manual-copy">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-sb"><?php echo $view['translator']->trans('mautic.form.form.header.manualcopy'); ?></h5>
            </div>
            <div class="panel-body">
                <p><?php echo $view['translator']->trans('mautic.form.form.help.manualcopy'); ?></p>
                <textarea class="form-html form-control" readonly onclick="this.setSelectionRange(0, this.value.length);"><?php echo htmlentities($form->getCachedHtml()); ?></textarea>
            </div>
            <div class="panel-footer text-right">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!--/ #modal-manual-copy -->