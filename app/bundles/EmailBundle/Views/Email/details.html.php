<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');
$view['slots']->set("headerTitle", $email->getSubject());

$isVariant = $email->isVariant(true);

$edit = $security->hasEntityAccess($permissions['email:emails:editown'], $permissions['email:emails:editother'], $email->getCreatedBy());
$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'item'       => $email,
    'templateButtons' => array(
        'edit'       => $edit,
        'delete'     => $security->hasEntityAccess($permissions['email:emails:deleteown'], $permissions['email:emails:deleteother'], $email->getCreatedBy()),
        'abtest'     => (!$isVariant && $edit && $permissions['email:emails:create'])
    ),
    'routeBase'  => 'email',
    'nameGetter' => 'getSubject',
    'customButtons' => array(
        array(
            'confirm' => array(
                'message'       => $view["translator"]->trans("mautic.email.form.confirmsend", array("%name%" => $email->getSubject() . " (" . $email->getId() . ")")),
                'confirmText'   => $view["translator"]->trans("mautic.email.send"),
                'confirmAction' => $view['router']->generate('mautic_email_action', array("objectAction" => "send", "objectId" => $email->getId())),
                'iconClass'     => 'fa fa-send-o',
                'btnText'       => $view["translator"]->trans("mautic.email.send")
            )
        )
    )
)));
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- email detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-2 text-right">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $email)); ?>
                    </div>
                </div>
            </div>
            <!--/ email detail header -->

            <!-- email detail collapseable -->
            <div class="collapse" id="email-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                                <?php echo $view->render('MauticCoreBundle:Helper:details.html.php', array('entity' => $email)); ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans('mautic.email.form.template'); ?></span></td>
                                    <td><?php echo $email->getTemplate(); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ email detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- email detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.details'); ?>">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse" data-target="#email-details"><span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ email detail collapseable toggler -->

            <!--
            some stats: need more input on what type of form data to show.
            delete if it is not require
            -->
            <div class="pa-md">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel">
                            <div class="panel-body box-layout">
                                <div class="col-xs-4 va-m">
                                    <h5 class="text-white dark-md fw-sb mb-xs">
                                        <span class="fa fa-download"></span>
                                        <?php echo $view['translator']->trans('mautic.email.lead.list.comparison'); ?>
                                    </h5>
                                </div>
                                <div class="col-xs-8 va-m" id="legend"></div>
                            </div>
                            <div class="pt-0 pl-15 pb-10 pr-15">
                                <div>
                                    <canvas id="list-compare-chart" height="300"></canvas>
                                </div>
                            </div>
                            <div id="list-compare-chart-data" class="hide"><?php echo json_encode($stats); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!--/ some stats -->
        </div>

        <?php if (count($variants['children'])): ?>
        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #variants-container -->
            <div class="tab-pane active bdr-w-0" id="variants-container">
                <!-- header -->
                <?php if ($variants['parent']->getVariantStartDate() != null): ?>
                    <div class="box-layout mb-lg">
                        <div class="col-xs-10 va-m">
                            <h4><?php echo $view['translator']->trans('mautic.email.variantstartdate', array(
                                    '%time%' => $view['date']->toTime($variants['parent']->getVariantStartDate()),
                                    '%date%' => $view['date']->toShort($variants['parent']->getVariantStartDate()),
                                    '%full%' => $view['date']->toTime($variants['parent']->getVariantStartDate())
                                ));?></h4>
                        </div>
                        <!-- button -->
                        <div class="col-xs-2 va-m text-right">
                            <a href="#" data-toggle="modal" data-target="#abStatsModal" class="btn btn-primary"><?php echo $view['translator']->trans('mautic.email.abtest.stats'); ?></a>
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
                                                    'model' => 'page.page',
                                                    'size'  => '',
                                                    'disableToggle' => true
                                                )); ?>
                                            </h3>
                                        </div>
                                        <div class="col-xs-11">
                                            <?php if ($isWinner): ?>
                                                <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.email.abtest.parentwinning'); ?>">
                                                    <a class="btn btn-default disabled" href="javascript:void(0);">
                                                        <i class="fa fa-trophy"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <h5 class="fw-sb text-primary">
                                                <a href="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'view', 'objectId' => $variants['parent']->getId())); ?>" data-toggle="ajax"><?php echo $variants['parent']->getSubject(); ?>
                                                    <?php if ($variants['parent']->getId() == $email->getId()) : ?>
                                                        <span>[<?php echo $view['translator']->trans('mautic.email.current'); ?>]</span>
                                                    <?php endif; ?>
                                                    <span>[<?php echo $view['translator']->trans('mautic.email.parent'); ?>]</span>
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
                                                        'model' => 'page',
                                                        'size'  => '',
                                                        'disableToggle' => true
                                                    )); ?>
                                                </h3>
                                            </div>
                                            <div class="col-xs-11">
                                                <?php if ($isWinner): ?>
                                                    <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.email.abtest.makewinner'); ?>">
                                                        <a class="btn btn-warning" href="javascript:void(0);" onclick="Mautic.showConfirmation('<?php echo $view->escape($view["translator"]->trans("mautic.email.abtest.confirmmakewinner", array("%name%" => $variant->getSubject() . " (" . $variant->getId() . ")")), 'js'); ?>', '<?php echo $view->escape($view["translator"]->trans("mautic.email.abtest.makewinner"), 'js'); ?>', 'executeAction', ['<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'winner', 'objectId' => $variant->getId())); ?>', ''],'<?php echo $view->escape($view["translator"]->trans("mautic.core.form.cancel"), 'js'); ?>','',[]);">
                                                            <i class="fa fa-trophy"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <h5 class="fw-sb text-primary">
                                                    <a href="<?php echo $view['router']->generate('mautic_email_action', array('objectAction' => 'view', 'objectId' => $variant->getId())); ?>" data-toggle="ajax"><?php echo $variant->getSubject(); ?>
                                                        <?php if ($variant->getId() == $email->getId()) : ?>
                                                            <span>[<?php echo $view['translator']->trans('mautic.email.current'); ?>]</span>
                                                        <?php endif; ?>
                                                    </a>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 va-t text-right">
                                        <em class="text-white dark-sm">
                                            <?php if ($isPublished && ($totalWeight > 100 || ($thisCriteria && $firstCriteria != $thisCriteria))): ?>
                                                <div class="text-danger" data-toggle="label label-danger" title="<?php echo $view['translator']->trans('mautic.email.variant.misconfiguration'); ?>"><div><span class="badge"><?php echo $weight; ?>%</span></div><div><i class="fa fa-fw fa-exclamation-triangle"></i><?php echo $criteriaLabel; ?></div></div>
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
        </div>
        <?php endif; ?>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <!-- preview URL -->
        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.email.urlvariant'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control" readonly
                           value="<?php echo $previewUrl; ?>" />
                <span class="input-group-btn">
                    <button class="btn btn-default" onclick="window.open('<?php echo $previewUrl; ?>', '_blank');">
                        <i class="fa fa-external-link"></i>
                    </button>
                </span>
                </div>
            </div>
        </div>
        <!--/ preview URL -->

        <div class="panel bg-transparent shd-none bdr-rds-0 bdr-w-0 mt-sm mb-0">
            <div class="panel-heading">
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.email.recipient.lists'); ?> (<?php
                echo $stats['datasets'][0]['data'][0] . '/' . $stats['datasets'][0]['data'][3]; ?>)</div>
            </div>
            <div class="panel-body pt-xs">
                <ul class="fa-ul">
                    <?php
                    $lists = $email->getLists();
                    foreach ($lists as $l):
                    ?>
                    <li><i class="fa-li fa fa-send"></i><?php echo $l->getName(); ?> (<?php echo (isset($stats[$l->getId()]) ?
                            $stats['datasets'][$l->getId()]['data'][0] . '/' . $stats['datasets'][$l->getId()]['data'][3] : '0/0/0'); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <hr class="hr-w-2" style="width:50%">

        <!-- activity feed -->
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', array('logs' => $logs)); ?>
    </div>
    <!--/ right section -->
    <input id="itemId" type="hidden" value="<?php echo $email->getId(); ?>" />
</div>
<!--/ end: box layout -->
<?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'abStatsModal',
    'header' => false,
    'body'   => (isset($abTestResults['supportTemplate'])) ? $view->render($abTestResults['supportTemplate'], array('results' => $abTestResults, 'variants' => $variants)) : $view['translator']->trans('mautic.email.abtest.noresults'),
    'size'   => 'lg'
)); ?>

