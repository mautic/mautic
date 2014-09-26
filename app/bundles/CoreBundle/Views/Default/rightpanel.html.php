<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\HttpKernel\Controller\ControllerReference;

$value = $app->getSession()->get('mautic.global_search');
?>

<!-- start: sidebar-header -->
<div class="sidebar-header">

</div>
<!--/ end: sidebar-header -->

<!-- start: sidebar-content -->
<div class="sidebar-content">
    <!-- scroll-content -->
    <div class="scroll-content slimscroll">
        <div class="offcanvas-container" data-toggle="offcanvas" data-options="{&quot;openerClass&quot;:&quot;offcanvas-opener&quot;, &quot;closerClass&quot;:&quot;offcanvas-closer&quot;}">
            <div class="offcanvas-wrapper pa-md">
                <div class="offcanvas-left">

                </div>

                <div class="offcanvas-content">
                    <div class="content slimscroll">
                        <?php echo $view['actions']->render(new ControllerReference('MauticChatBundle:Default:index')); ?>
                        <div class="scrollbar" style="width: 8px; position: absolute; top: 0px; opacity: 0.4; border-top-left-radius: 7px; border-top-right-radius: 7px; border-bottom-right-radius: 7px; border-bottom-left-radius: 7px; z-index: 99; right: 0px; height: 597px; display: none; background: rgb(0, 0, 0);"></div>
                        <div class="scrollrail" style="width: 8px; height: 100%; position: absolute; top: 0px; display: block; border-top-left-radius: 7px; border-top-right-radius: 7px; border-bottom-right-radius: 7px; border-bottom-left-radius: 7px; opacity: 0.2; z-index: 90; right: 0px; background: rgb(51, 51, 51);"></div>
                    </div>
                </div>

                <div class="offcanvas-right has-footer">
                    <div class="header pl0 pr0">
                        <ul class="list-table nm">
                            <li style="width:50px;">
                                <a href="javascript:void(0);" onclick="Mautic.updateChatList(true);" class="btn btn-link text-default offcanvas-closer"><i class="fa fa-lg fa-fw fa-arrow-circle-left"></i></a>
                            </li>
                            <li class="text-center">
                                <h5 class="semibold nm">
                                    <p class="nm" id="ChatHeader"></p>
                                    <small id="ChatSubHeader"></small>
                                </h5>
                            </li>
                            <li style="width:50px;" class="text-right"></li>
                        </ul>
                    </div>
                    <div class="content slimscroll" id="ChatConversation">
                        <?php echo $view['actions']->render(new ControllerReference('MauticChatBundle:Default:dm')); ?>
                    </div>

                    <?php echo $view->render('MauticChatBundle:User:footer.html.php'); ?>
                </div>
            </div>
        </div>
    </div>
    <!--/ scroll-content -->
</div>
<!--/ end: sidebar-content -->