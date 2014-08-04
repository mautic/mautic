<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<div class="img-grid">
    <ul class="list-unstyled row">
        <li class="col-xs-8">
            <!-- thumbnail -->
            <div class="thumbnail">
                <!-- media -->
                <div class="media" style="height:310px;">
                    <!-- indicator -->
                    <div class="indicator"><span class="spinner"></span></div>
                    <!--/ indicator -->
                    <!-- toolbar overlay -->
                            <div class="overlay">
                                <div class="toolbar">
                                    <a href="<?php echo $photos[0]['url']; ?>" data-toggle="modal" data-target="#bs-modal-sm" class="btn btn-teal"><i class="fa fa-search"></i></a>
                                </div>
                            </div>                    
                    <!--/ toolbar overlay -->
                    <img data-toggle="unveil" src="<?php echo $photos[0]['url']; ?>" data-src="<?php echo $photos[0]['url']; ?>" alt="Photo" class="unveiled">
                </div>
                <!--/ media -->
            </div>
            <!--/ thumbnail -->
        </li>
        <li class="col-xs-4">
            <ul class="list-unstyled row">
                <li class="col-xs-12">
                    <!-- thumbnail -->
                    <div class="thumbnail">
                        <!-- media -->
                        <div class="media" style="height:100px;">
                            <!-- indicator -->
                            <div class="indicator"><span class="spinner"></span></div>
                            <!--/ indicator -->
                            <!-- toolbar overlay -->
                            <div class="overlay">
                                <div class="toolbar">
                                    <a href="<?php echo $photos[1]['url']; ?>" data-toggle="modal" data-target="#bs-modal-sm" class="btn btn-teal"><i class="fa fa-search"></i></a>
                                </div>
                            </div>
                            <!--/ toolbar overlay -->
                            <img data-toggle="unveil" src="<?php echo $photos[1]['url']; ?>" data-src="<?php echo $photos[1]['url']; ?>" alt="Photo" class="unveiled" width="100%">
                        </div>
                        <!--/ media -->
                    </div>
                    <!--/ thumbnail -->
                </li>
            </ul>
            <ul class="list-unstyled row">
                <li class="col-xs-12">
                    <!-- thumbnail -->
                    <div class="thumbnail">
                        <!-- media -->
                        <div class="media" style="height:100px;">
                            <!-- indicator -->
                            <div class="indicator"><span class="spinner"></span></div>
                            <!--/ indicator -->
                            <!-- toolbar overlay -->
                            <div class="overlay">
                                <div class="toolbar">
                                    <a href="<?php echo $photos[2]['url']; ?>" data-toggle="modal" data-target="#bs-modal-sm" class="btn btn-teal"><i class="fa fa-search"></i></a>
                                </div>
                            </div>
                            <!--/ toolbar overlay -->
                            <img data-toggle="unveil" src="<?php echo $photos[2]['url']; ?>" data-src="<?php echo $photos[2]['url']; ?>" alt="Photo" class="unveiled" width="100%">
                        </div>
                        <!--/ media -->
                    </div>
                    <!--/ thumbnail -->
                </li>
            </ul>
            <ul class="list-unstyled row">
                <li class="col-xs-12">
                    <!-- thumbnail -->
                    <div class="thumbnail">
                        <!-- media -->
                        <div class="media" style="height:100px;">
                            <!-- indicator -->
                            <div class="indicator"><span class="spinner"></span></div>
                            <!--/ indicator -->
                            <!-- toolbar overlay -->
                            <div class="overlay">
                                <div class="toolbar">
                                    <a href="<?php echo $photos[3]['url']; ?>" data-toggle="modal" data-target="#bs-modal-sm" class="btn btn-teal"><i class="fa fa-search"></i></a>
                                </div>
                            </div>
                            <!--/ toolbar overlay -->
                            <img data-toggle="unveil" src="<?php echo $photos[3]['url']; ?>" data-src="<?php echo $photos[3]['url']; ?>" alt="Photo" class="unveiled" width="100%">
                        </div>
                        <!--/ media -->
                    </div>
                    <!--/ thumbnail -->
                </li>
            </ul>
        </li>
    </ul>
</div>

<!-- Modal -->
<div class="modal fade" id="bs-modal-sm" tabindex="-1" role="dialog" aria-labelledby="bs-modal-sm" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        </div><!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
