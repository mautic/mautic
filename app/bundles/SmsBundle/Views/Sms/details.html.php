<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'sms');
$view['slots']->set("headerTitle", $sms->getName());

$smsType    = $sms->getSmsType();
if (empty($smsType)) {
    $smsType = 'template';
}

$customButtons = array();

$edit = $view['security']->hasEntityAccess($permissions['sms:smses:editown'], $permissions['sms:smses:editother'], $sms->getCreatedBy());
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'item'       => $sms,
    'templateButtons' => array(
        'delete'     => $view['security']->hasEntityAccess($permissions['sms:smses:deleteown'], $permissions['sms:smses:deleteother'], $sms->getCreatedBy())
    ),
    'routeBase'  => 'sms',
    'preCustomButtons' => $customButtons
)));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto bg-dark-xs">
            <!-- sms detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#sms-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ sms detail collapseable toggler -->

           <?php 
           // echo $view->render('MauticSmsBundle:Sms:' . $smsType . '_graph.html.php',
           //     array(
           //         'stats' => $stats,
           //         'sms'  => $sms
           //     )
           // ); 
           ?>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0" id="clicks-container">
                <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', array('trackables' => $trackables )); ?>
            </div>
        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $sms->getId(); ?>" />
</div>
<!--/ end: box layout -->
