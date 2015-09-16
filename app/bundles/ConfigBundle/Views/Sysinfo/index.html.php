<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'sysinfo');
$view['slots']->set("headerTitle", $view['translator']->trans('System Info'));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- step container -->
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <!-- Nav tabs -->
            <ul class="list-group list-group-tabs" role="tablist">
                <li role="presentation" class="list-group-item in active">
                    <a href="#phpinfo" aria-controls="phpinfo" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.sysinfo.tab.phpinfo'); ?>
                    </a>
                </li>
            </ul>

        </div>
    </div>

    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-l">

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active bdr-w-0" id="phpinfo">
                <div class="pt-md pr-md pl-md pb-md">
                    <?php echo $phpInfo;// $view->render('MauticConfigBundle:Sysinfo:phpinfo.html.php', array('phpInfo' => $phpInfo)); ?>
                </div>
            </div>
        </div>

    </div>
</div>
