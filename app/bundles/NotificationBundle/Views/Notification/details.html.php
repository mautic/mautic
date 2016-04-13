<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'notification');
$view['slots']->set("headerTitle", $notification->getName());

$notificationType    = $notification->getNotificationType();
if (empty($notificationType)) {
    $notificationType = 'template';
}

$customButtons = array();

if ($notificationType == 'list') {
    $customButtons[] = array(
        'attr' => array(
            'data-toggle' => 'ajax',
            'href'        => $view['router']->generate('mautic_notification_action', array('objectAction' => 'send', 'objectId' => $notification->getId())),
        ),
        'iconClass' => 'fa fa-send-o',
        'btnText'   => 'mautic.notification.send'
    );
}

$customButtons[] = array(
    'attr' => array(
        'data-toggle' => 'ajax',
        'href'        => $view['router']->generate('mautic_notification_action', array('objectAction' => 'example', 'objectId' => $notification->getId())),
    ),
    'iconClass' => 'fa fa-send',
    'btnText'   => 'mautic.notification.send.example'
);

$customButtons[] = array(
    'attr' => array(
        'data-toggle' => 'ajax',
        'href'        => $view['router']->generate('mautic_notification_action', array("objectAction" => "clone", "objectId" => $notification->getId())),
        ),
        'iconClass' => 'fa fa-copy',
        'btnText'   => 'mautic.core.form.clone'
);

$edit = $view['security']->hasEntityAccess($permissions['notification:notifications:editown'], $permissions['notification:notifications:editother'], $notification->getCreatedBy());
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'item'       => $notification,
    'templateButtons' => array(
        'edit'       => $edit,
        'delete'     => $view['security']->hasEntityAccess($permissions['notification:notifications:deleteown'], $permissions['notification:notifications:deleteother'], $notification->getCreatedBy())
    ),
    'routeBase'  => 'notification',
    'preCustomButtons' => $customButtons
)));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- notification detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div><?php echo \Mautic\CoreBundle\Helper\EmojiHelper::toHtml($notification->getSubject(), 'short'); ?></div>
                        <div class="text-muted"><?php echo $notification->getDescription(); ?></div>
                    </div>
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $notification)); ?>
                    </div>
                </div>
            </div>
            <!--/ notification detail header -->

            <!-- notification detail collapseable -->
            <div class="collapse" id="notification-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $notification)); ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.core.form.theme'); ?></span></td>
                                    <td><?php echo $notification->getTemplate(); ?></td>
                                </tr>
                                <?php if ($fromName = $notification->getFromName()): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.notification.from_name'); ?></span></td>
                                    <td><?php echo $fromName; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($fromNotification = $notification->getFromAddress()): ?>
                                    <tr>
                                        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.notification.from_notification'); ?></span></td>
                                        <td><?php echo $fromNotification; ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($replyTo = $notification->getReplyToAddress()): ?>
                                    <tr>
                                        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.notification.reply_to_notification'); ?></span></td>
                                        <td><?php echo $replyTo; ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($bccAddress = $notification->getBccAddress()): ?>
                                    <tr>
                                        <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.notification.bcc'); ?></span></td>
                                        <td><?php echo $bccAddress; ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ notification detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- notification detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#notification-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ notification detail collapseable toggler -->

           <?php echo $view->render('MauticNotificationBundle:Notification:' . $notificationType . '_graph.html.php',
               array(
                   'stats'        => $stats,
                   'notification'        => $notification
               )
           ); ?>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.notification.click_tracks'); ?>
                    </a>
                </li>
                <?php if ($showVariants): ?>
                <li>
                    <a href="#variants-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.notification.variants'); ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0" id="clicks-container">
                <?php if (!empty($trackableLinks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered click-list">
                        <thead>
                            <tr>
                                <td><?php echo $view['translator']->trans('mautic.page.url'); ?></td>
                                <td><?php echo $view['translator']->trans('mautic.notification.click_count'); ?></td>
                                <td><?php echo $view['translator']->trans('mautic.notification.click_unique_count'); ?></td>
                                <td><?php echo $view['translator']->trans('mautic.notification.click_track_id'); ?></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trackableLinks as $link): ?>
                            <tr>
                                <td><a href="<?php echo $link['url']; ?>"><?php echo $link['url']; ?></a></td>
                                <td class="text-center"><?php echo $link['hits']; ?></td>
                                <td class="text-center"><?php echo $link['unique_hits']; ?></td>
                                <td><?php echo $link['redirect_id']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array(
                        'header'  => 'mautic.notification.click_tracks.header_none',
                        'message' => 'mautic.notification.click_tracks.none'
                    )); ?>
                    <div class="clearfix"></div>
                <?php endif; ?>
            </div>

            <?php if ($showVariants): ?>
            <!-- #variants-container -->
            <div class="tab-pane bdr-w-0" id="variants-container">
                <!-- header -->
                <?php if ($variants['parent']->getVariantStartDate() != null): ?>
                    <div class="box-layout mb-lg">
                        <div class="col-xs-10 va-m">
                            <h4><?php echo $view['translator']->trans('mautic.notification.variantstartdate', array(
                                    '%time%' => $view['date']->toTime($variants['parent']->getVariantStartDate()),
                                    '%date%' => $view['date']->toShort($variants['parent']->getVariantStartDate()),
                                    '%full%' => $view['date']->toTime($variants['parent']->getVariantStartDate())
                                ));?></h4>
                        </div>
                        <!-- button -->
                        <div class="col-xs-2 va-m text-right">
                            <a href="#" data-toggle="modal" data-target="#abStatsModal" class="btn btn-primary"><?php echo $view['translator']->trans('mautic.notification.abtest.stats'); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
                <!--/ header -->

                <!-- start: variants list -->
                <ul class="list-group">
                    <?php if ($variants['parent']) : ?>
                        <?php $isWinner = (isset($abTestResults['winners']) && in_array($variants['parent']->getId(), $abTestResults['winners']) && $variants['parent']->getVariantStartDate() && $variants['parent']->isPublished()); ?>
                        <li class="list-group-item bg-auto bg-light-xs">
                            <div class="box-layout">
                                <div class="col-md-8 va-m">
                                    <div class="row">
                                        <div class="col-xs-1">
                                            <h3>
                                                <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php', array(
                                                    'item'  => $variants['parent'],
                                                    'model' => 'notification',
                                                    'size'  => '',
                                                    'query' => 'size=',
                                                    'disableToggle' => ($notificationType == 'list')
                                                )); ?>
                                            </h3>
                                        </div>
                                        <div class="col-xs-11">
                                            <?php if ($isWinner): ?>
                                                <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.notification.abtest.parentwinning'); ?>">
                                                    <a class="btn btn-default disabled" href="javascript:void(0);">
                                                        <i class="fa fa-trophy"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <h5 class="fw-sb text-primary">
                                                <a href="<?php echo $view['router']->generate('mautic_notification_action', array('objectAction' => 'view', 'objectId' => $variants['parent']->getId())); ?>" data-toggle="ajax"><?php echo $variants['parent']->getName(); ?>
                                                    <?php if ($variants['parent']->getId() == $notification->getId()) : ?>
                                                        <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                                                    <?php endif; ?>
                                                    <span>[<?php echo $view['translator']->trans('mautic.core.parent'); ?>]</span>
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 va-t text-right">
                                    <em class="text-white dark-sm"><span class="label label-success"><?php echo (int) $variants['properties'][$variants['parent']->getId()]['weight']; ?>%</span></em>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                    <?php $totalWeight = (int) $variants['properties'][$variants['parent']->getId()]['weight']; ?>
                    <?php if (count($variants['children'])): ?>
                        <?php /** @var \Mautic\PageBundle\Entity\Page $variant */ ?>
                        <?php foreach ($variants['children'] as $id => $variant) :
                            if (!isset($variants['properties'][$id])):
                                $settings = $variant->getVariantSettings();
                                $variants['properties'][$id] = $settings;
                            endif;

                            if (!empty($variants['properties'][$id])):
                                $thisCriteria  = $variants['properties'][$id]['winnerCriteria'];
                                $weight        = (int) $variants['properties'][$id]['weight'];
                                $criteriaLabel = ($thisCriteria) ? $view['translator']->trans($variants['criteria'][$thisCriteria]['label']) : '';
                            else:
                                $thisCriteria = $criteriaLabel = '';
                                $weight       = 0;
                            endif;

                            $isPublished    = $variant->isPublished();
                            $totalWeight   += ($isPublished) ? $weight : 0;
                            $firstCriteria  = (!isset($firstCriteria)) ? $thisCriteria : $firstCriteria;
                            $isWinner       = (isset($abTestResults['winners']) && in_array($variant->getId(), $abTestResults['winners']) && $variants['parent']->getVariantStartDate() && $isPublished);
                            ?>

                            <li class="list-group-item bg-auto bg-light-xs">
                                <div class="box-layout">
                                    <div class="col-md-8 va-m">
                                        <div class="row">
                                            <div class="col-xs-1">
                                                <h3>
                                                    <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_icon.html.php', array(
                                                        'item'  => $variant,
                                                        'model' => 'notification',
                                                        'size'  => '',
                                                        'query' => 'size='
                                                    )); ?>
                                                </h3>
                                            </div>
                                            <div class="col-xs-11">
                                                <?php if ($isWinner): ?>
                                                    <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.notification.abtest.makewinner'); ?>">
                                                        <a class="btn btn-warning"
                                                           data-toggle="confirmation"
                                                           href="<?php echo $view['router']->generate('mautic_notification_action', array('objectAction' => 'winner', 'objectId' => $variant->getId())); ?>"
                                                           data-toggle="confirmation"
                                                           data-mesasge="<?php echo $view->escape($view["translator"]->trans("mautic.notification.abtest.confirmmakewinner", array("%name%" => $variant->getName()))); ?>"
                                                           data-confirm-text="<?php echo $view->escape($view["translator"]->trans("mautic.notification.abtest.makewinner")); ?>"
                                                           data-confirm-callback="executeAction"
                                                           data-cancel-text="<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel")); ?>">
                                                            <i class="fa fa-trophy"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <h5 class="fw-sb text-primary">
                                                    <a href="<?php echo $view['router']->generate('mautic_notification_action', array('objectAction' => 'view', 'objectId' => $variant->getId())); ?>" data-toggle="ajax"><?php echo $variant->getName(); ?>
                                                        <?php if ($variant->getId() == $notification->getId()) : ?>
                                                            <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                                                        <?php endif; ?>
                                                    </a>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 va-t text-right">
                                        <em class="text-white dark-sm">
                                            <?php if ($isPublished && ($totalWeight > 100 || ($thisCriteria && $firstCriteria != $thisCriteria))): ?>
                                                <div class="text-danger" data-toggle="label label-danger" title="<?php echo $view['translator']->trans('mautic.notification.variant.misconfiguration'); ?>"><div><span class="badge"><?php echo $weight; ?>%</span></div><div><i class="fa fa-fw fa-exclamation-triangle"></i><?php echo $criteriaLabel; ?></div></div>
                                            <?php elseif ($isPublished && $criteriaLabel): ?>
                                                <div class="text-success"><div><span class="label label-success"><?php echo $weight; ?>%</span></div><div><i class="fa fa-fw fa-check"></i><?php echo $criteriaLabel; ?></div></div>
                                            <?php elseif ($thisCriteria): ?>
                                                <div class="text-muted"><div><span class="label label-default"><?php echo $weight; ?>%</span></div><div><?php echo $criteriaLabel; ?></div></div>
                                            <?php endif; ?>
                                        </em>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <!--/ end: variants list -->
            </div>
            <!--/ #variants-container -->
            <?php endif; ?>
        </div>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.notification.urlvariant'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                           value="<?php echo $previewUrl; ?>" />
                <span class="input-group-btn">
                    <button class="btn btn-default btn-nospin" onclick="window.open('<?php echo $previewUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
                </div>
            </div>
        </div>

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $notification->getId(); ?>" />
</div>
<!--/ end: box layout -->
<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'abStatsModal',
    'header' => false,
    'body'   => (isset($abTestResults['supportTemplate'])) ? $view->render($abTestResults['supportTemplate'], array('results' => $abTestResults, 'variants' => $variants)) : $view['translator']->trans('mautic.notification.abtest.noresults'),
    'size'   => 'lg'
)); ?>
