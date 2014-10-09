<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$value = $app->getSession()->get('mautic.global_search');
?>

<div class="offcanvas-container" data-toggle="offcanvas" data-options='{"openerClass":"offcanvas-opener", "closerClass":"offcanvas-closer"}'>
    <!-- START Wrapper -->
    <div class="offcanvas-wrapper">
        <!--
        Offcanvas Left:
        for this container, we can put the add channel
        form
        -->
        <form class="offcanvas-left" action="">
            <!-- start: sidebar header -->
            <div class="sidebar-header box-layout">
                <div class="col-xs-6 va-m">
                    <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-left fs-16"></span></a>
                </div>
                <div class="col-xs-6 va-m text-right">
                    <a href="javascript:void(0);"><span class="fa fa-info fs-16"></span></a>
                </div>
            </div>
            <!--/ end: sidebar header -->

            <!-- start: sidebar footer -->
            <div class="sidebar-footer box-layout">
                <div class="col-xs-6 va-m">
                    <button type="submit" class="btn btn-default">
                        <i class="fa fa-save mr-xs"></i> Save
                    </button>
                </div>
                <div class="col-xs-6 va-m clearfix">
                    <button type="reset" class="btn btn-default pull-right">
                        <i class="fa fa-times text-danger mr-xs"></i> Cancel
                    </button>
                </div>
            </div>
            <!--/ end: sidebar footer -->

            <!-- start: sidebar content -->
            <div class="sidebar-content">
                <!-- scroll-content -->
                <div class="scroll-content slimscroll">
                    <div class="pa-md">
                        <div class="form-group">
                            <label for="">Channel handle</label>
                            <div class="input-group input-group">
                                <span class="input-group-addon">#</span>
                                <input type="text" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Short description</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <!--/ scroll-content -->
            </div>
            <!--/ end: sidebar content -->
        </form>
        <!--/ Offcanvas Left -->

        <!--
        Offcanvas Main:
        This will be default/initial view if sidebar right is in open
        state. Put the chat list here.
        -->
        <div class="offcanvas-main">
            <!-- start: sidebar header -->
            <div class="sidebar-header box-layout">
                <div class="col-xs-6 va-m">
                    <h5 class="fw-sb">Channels</h5>
                </div>
                <div class="col-xs-6 va-m text-right">
                    <!-- this will toggle offcanvas-left container-->
                    <a href="javascript:void(0);" class="btn btn-primary offcanvas-opener offcanvas-open-ltr">Add</a>
                </div>
            </div>
            <!--/ end: sidebar header -->

            <!-- start: sidebar content -->
            <div class="sidebar-content">
                <!-- scroll-content -->
                <div class="scroll-content slimscroll">
                    <!-- put the chat list here -->
                    <ul class="media-list media-list-contact">
                        <li class="media-heading">CONTACT GROUP #1</li>
                        <li class="media">
                            <a href="javascript:void(0);" class="clearfix offcanvas-opener offcanvas-open-rtl">
                                <span class="pull-left img-wrapper img-rounded mr-sm">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/jennyshen/128.jpg" style="width:36px">
                                </span>
                                <span class="media-body">
                                    <span class="media-heading mb-0 text-white dark-sm">
                                        <span class="bullet bullet-success mr-xs"></span> Jenny Sheng
                                    </span>
                                    <span class="meta text-white dark-lg">Online</span>
                                </span>
                            </a>
                        </li>
                        <li class="media">
                            <a href="javascript:void(0);" class="clearfix offcanvas-opener offcanvas-open-rtl">
                                <span class="pull-left img-wrapper img-rounded mr-sm">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/ademilter/128.jpg" style="width:36px">
                                </span>
                                <span class="media-body">
                                    <span class="media-heading mb-0 text-white dark-sm">
                                        <span class="bullet bullet-default mr-xs"></span> Ade Milter
                                    </span>
                                    <span class="meta text-white dark-lg">Offline</span>
                                </span>
                            </a>
                        </li>
                    </ul>

                    <ul class="media-list media-list-contact">
                        <li class="media-heading">CONTACT GROUP #2</li>
                        <li class="media">
                            <a href="javascript:void(0);" class="clearfix offcanvas-opener offcanvas-open-rtl">
                                <span class="pull-left img-wrapper img-rounded mr-sm">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/nilo/128.jpg" style="width:36px">
                                </span>
                                <span class="media-body">
                                    <span class="media-heading mb-0 text-white dark-sm">
                                        <span class="bullet bullet-warning mr-xs"></span> Nilo
                                    </span>
                                    <span class="meta text-white dark-lg">Away</span>
                                </span>
                            </a>
                        </li>
                        <li class="media">
                            <a href="javascript:void(0);" class="clearfix offcanvas-opener offcanvas-open-rtl">
                                <span class="pull-left img-wrapper img-rounded mr-sm">
                                    <img class="media-object" src="https://s3.amazonaws.com/uifaces/faces/twitter/raquelromanp/128.jpg" style="width:36px">
                                </span>
                                <span class="media-body">
                                    <span class="media-heading mb-0 text-white dark-sm">
                                        <span class="bullet bullet-danger mr-xs"></span> Raquel Romanp
                                    </span>
                                    <span class="meta text-white dark-lg">Don't Disturbe</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
                <!--/ scroll-content -->
            </div>
            <!--/ end: sidebar content -->
        </div>
        <!--/ Offcanvas Content -->

        <!--
        Offcanvas Right:
        this will be the chat bubbles container. This container will be
        trigger by chat list in `offcanvas-main`
        -->
        <div class="offcanvas-right">
            <!-- start: sidebar header -->
            <div class="sidebar-header box-layout">
                <div class="col-xs-6 va-m">
                    <a href="javascript:void(0);" class="offcanvas-closer"><span class="fa fa-arrow-left fs-16"></span></a>
                </div>
                <div class="col-xs-6 va-m text-right">
                    <a href="javascript:void(0);"><span class="fa fa-info fs-16"></span></a>
                </div>
            </div>
            <!--/ end: sidebar header -->

            <!-- start: sidebar footer -->
            <div class="sidebar-footer box-layout">
                <div class="cell va-m">
                    <div class="form-control-icon">
                        <input type="text" class="form-control bg-transparent bdr-rds-0 bdr-w-0" placeholder="Type something...">
                        <span class="the-icon fa fa-paper-plane text-white dark-sm"></span><!-- must below `form-control` -->
                    </div>
                </div>
            </div>
            <!--/ end: sidebar footer -->

            <!-- start: sidebar content -->
            <div class="sidebar-content">
                <!-- scroll-content -->
                <div class="scroll-content slimscroll">
                    <!-- put the chat bubbles here -->
                    <ul class="media-list media-list-bubble">
                        <li class="media media-right">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/soyjavi/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">eros non enim commodo hendrerit.</p>
                                <span class="clearfix"></span>
                                <p class="media-text">Suspendisse dui.</p>
                                <span class="clearfix"></span>
                                <p class="media-text">eu nulla at</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <small class="text-white dark-lg">Sun, Mar 02</small>
                            </div>
                        </li>
                        <li class="media">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/andrewaashen/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">Etiam laoreet, libero et tristique pellentesque, tellus sem mollis dui, in sodales elit erat.</p>
                                <span class="clearfix"></span>
                                <p class="media-text">faucibus ut, nulla. Cras eu tellus</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <small class="media-meta">Tue, Oct 01</small>
                            </div>
                        </li>
                        <li class="media media-right">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/soyjavi/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">Duis a mi fringilla mi lacinia mattis. Integer</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <p class="media-meta">Fri, Sep 27</p>
                            </div>
                        </li>
                        <li class="media">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/andrewaashen/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">Praesent interdum ligula eu enim. Etiam imperdiet dictum magna.</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <p class="media-meta">Wed, Aug 28</p>
                            </div>
                        </li>
                        <li class="media media-right">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/soyjavi/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">Aliquam rutrum lorem ac risus. Morbi metus. Vivamus euismod urna.</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <p class="media-meta">Sat, Sep 27</p>
                            </div>
                        </li>
                        <li class="media">
                            <a href="javascript:void(0);" class="media-object">
                                <span class="img-wrapper img-rounded">
                                    <img src="https://s3.amazonaws.com/uifaces/faces/twitter/andrewaashen/128.jpg" style="width:36px">
                                </span>
                            </a>
                            <div class="media-body">
                                <p class="media-text">Vestibulum accumsan neque et nunc. Quisque ornare tortor at risus. Nunc ac</p>
                                <span class="clearfix"></span>
                                <p class="media-text">Nam porttitor scelerisque neque</p>
                                <!-- meta -->
                                <span class="clearfix"></span><!-- important: clearing floated media text -->
                                <p class="media-meta">Sun, Feb 22</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <!--/ scroll-content -->
            </div>
            <!--/ end: sidebar content -->
        </div>
        <!--/ Offcanvas Right -->
    </div>
    <!--/ END Wrapper -->
</div>