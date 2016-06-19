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
$view['slots']->set("headerTitle", $email->getName());

$isVariant    = $email->isVariant(true);
$showVariants = count($variants['children']);
$emailType    = $email->getEmailType();
$edit         = $view['security']->hasEntityAccess(
    $permissions['email:emails:editown'],
    $permissions['email:emails:editother'],
    $email->getCreatedBy()
);

if (empty($emailType)) {
    $emailType = 'template';
}

$customButtons = array();

if (!$isVariant && $edit && $permissions['email:emails:create']) {
    $customButtons[] = array(
        'attr'      => array(
            'data-toggle' => 'ajax',
            'href'        => $view['router']->path(
                'mautic_email_action',
                array("objectAction" => 'abtest', 'objectId' => $email->getId())
            ),
        ),
        'iconClass' => 'fa fa-sitemap',
        'btnText'   => $view['translator']->trans('mautic.core.form.abtest')
    );
}

if ($emailType == 'list') {
    $customButtons[] = array(
        'attr'      => array(
            'data-toggle' => 'ajax',
            'href'        => $view['router']->path(
                'mautic_email_action',
                array('objectAction' => 'send', 'objectId' => $email->getId())
            ),
        ),
        'iconClass' => 'fa fa-send-o',
        'btnText'   => 'mautic.email.send'
    );
}

$customButtons[] = array(
    'attr'      => array(
        'data-toggle' => 'ajax',
        'href'        => $view['router']->path(
            'mautic_email_action',
            array('objectAction' => 'example', 'objectId' => $email->getId())
        ),
    ),
    'iconClass' => 'fa fa-send',
    'btnText'   => 'mautic.email.send.example'
);

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        array(
            'item'             => $email,
            'templateButtons'  => array(
                'edit'   => $edit,
                'clone'  => (!$isVariant && $edit && $permissions['email:emails:create']),
                'delete' => $view['security']->hasEntityAccess(
                    $permissions['email:emails:deleteown'],
                    $permissions['email:emails:deleteother'],
                    $email->getCreatedBy()
                ),
                'close'  => $view['security']->hasEntityAccess(
                    $permissions['email:emails:viewown'],
                    $permissions['email:emails:viewother'],
                    $email->getCreatedBy()
                ),
            ),
            'routeBase'        => 'email',
            'preCustomButtons' => $customButtons
        )
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $email))
);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- email detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10">
                        <div><?php echo \Mautic\CoreBundle\Helper\EmojiHelper::toHtml(
                                $email->getSubject(),
                                'short'
                            ); ?></div>
                        <div class="text-muted"><?php echo $email->getDescription(); ?></div>
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
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                array('entity' => $email)
                            ); ?>
                            <tr>
                                <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                            'mautic.core.form.theme'
                                        ); ?></span></td>
                                <td><?php echo $email->getTemplate(); ?></td>
                            </tr>
                            <?php if ($fromName = $email->getFromName()): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                                'mautic.email.from_name'
                                            ); ?></span></td>
                                    <td><?php echo $fromName; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($fromEmail = $email->getFromAddress()): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                                'mautic.email.from_email'
                                            ); ?></span></td>
                                    <td><?php echo $fromEmail; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($replyTo = $email->getReplyToAddress()): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                                'mautic.email.reply_to_email'
                                            ); ?></span></td>
                                    <td><?php echo $replyTo; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($bccAddress = $email->getBccAddress()): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b"><?php echo $view['translator']->trans(
                                                'mautic.email.bcc'
                                            ); ?></span></td>
                                    <td><?php echo $bccAddress; ?></td>
                                </tr>
                            <?php endif; ?>
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
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#email-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ email detail collapseable toggler -->

            <?php echo $view->render(
                'MauticEmailBundle:Email:'.$emailType.'_graph.html.php',
                array(
                    'stats'        => $stats,
                    'email'        => $email,
                    'showVariants' => $showVariants,
                    'isVariant'    => $isVariant,
                    'dateRangeForm' => $dateRangeForm
                )
            ); ?>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <li class="active">
                    <a href="#clicks-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.trackable.click_counts'); ?>
                    </a>
                </li>
                <?php if ($showVariants): ?>
                    <li>
                        <a href="#variants-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.email.variants'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <div class="tab-pane active bdr-w-0" id="clicks-container">
                <?php echo $view->render('MauticPageBundle:Trackable:click_counts.html.php', array('trackables' => $trackables )); ?>
            </div>

            <?php if ($showVariants): ?>
                <!-- #variants-container -->
                <div class="tab-pane bdr-w-0" id="variants-container">
                    <!-- header -->
                    <?php if ($variants['parent']->getVariantStartDate() != null): ?>
                        <div class="box-layout mb-lg">
                            <div class="col-xs-10 va-m">
                                <h4><?php echo $view['translator']->trans(
                                        'mautic.email.variantstartdate',
                                        array(
                                            '%time%' => $view['date']->toTime(
                                                $variants['parent']->getVariantStartDate()
                                            ),
                                            '%date%' => $view['date']->toShort(
                                                $variants['parent']->getVariantStartDate()
                                            ),
                                            '%full%' => $view['date']->toTime(
                                                $variants['parent']->getVariantStartDate()
                                            )
                                        )
                                    ); ?></h4>
                            </div>
                            <!-- button -->
                            <div class="col-xs-2 va-m text-right">
                                <a href="#" data-toggle="modal" data-target="#abStatsModal"
                                   class="btn btn-primary"><?php echo $view['translator']->trans(
                                        'mautic.email.abtest.stats'
                                    ); ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!--/ header -->

                    <!-- start: variants list -->
                    <ul class="list-group">
                        <?php if ($variants['parent']) : ?>
                            <?php $isWinner = (isset($abTestResults['winners'])
                                && in_array(
                                    $variants['parent']->getId(),
                                    $abTestResults['winners']
                                )
                                && $variants['parent']->getVariantStartDate()
                                && $variants['parent']->isPublished()); ?>
                            <li class="list-group-item bg-auto bg-light-xs">
                                <div class="box-layout">
                                    <div class="col-md-8 va-m">
                                        <div class="row">
                                            <div class="col-xs-1">
                                                <h3>
                                                    <?php echo $view->render(
                                                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                        array(
                                                            'item'          => $variants['parent'],
                                                            'model'         => 'email',
                                                            'size'          => '',
                                                            'query'         => 'size=',
                                                            'disableToggle' => ($emailType == 'list')
                                                        )
                                                    ); ?>
                                                </h3>
                                            </div>
                                            <div class="col-xs-11">
                                                <?php if ($isWinner): ?>
                                                    <div class="mr-xs pull-left" data-toggle="tooltip"
                                                         title="<?php echo $view['translator']->trans(
                                                             'mautic.email.abtest.parentwinning'
                                                         ); ?>">
                                                        <a class="btn btn-default disabled" href="javascript:void(0);">
                                                            <i class="fa fa-trophy"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <h5 class="fw-sb text-primary">
                                                    <a href="<?php echo $view['router']->path(
                                                        'mautic_email_action',
                                                        array(
                                                            'objectAction' => 'view',
                                                            'objectId'     => $variants['parent']->getId()
                                                        )
                                                    ); ?>" data-toggle="ajax"><?php echo $variants['parent']->getName(
                                                        ); ?>
                                                        <?php if ($variants['parent']->getId() == $email->getId()) : ?>
                                                            <span>[<?php echo $view['translator']->trans(
                                                                    'mautic.core.current'
                                                                ); ?>]</span>
                                                        <?php endif; ?>
                                                        <span>[<?php echo $view['translator']->trans(
                                                                'mautic.core.parent'
                                                            ); ?>]</span>
                                                    </a>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 va-t text-right">
                                        <em class="text-white dark-sm"><span
                                                class="label label-success"><?php echo (int) $variants['properties'][$variants['parent']->getId(
                                                )]['weight']; ?>%</span></em>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php $totalWeight = (int) $variants['properties'][$variants['parent']->getId()]['weight']; ?>
                        <?php if (count($variants['children'])): ?>
                            <?php /** @var \Mautic\PageBundle\Entity\Page $variant */ ?>
                            <?php foreach ($variants['children'] as $id => $variant) :
                                if (!isset($variants['properties'][$id])):
                                    $settings                    = $variant->getVariantSettings();
                                    $variants['properties'][$id] = $settings;
                                endif;

                                if (!empty($variants['properties'][$id])):
                                    $thisCriteria  = $variants['properties'][$id]['winnerCriteria'];
                                    $weight        = (int) $variants['properties'][$id]['weight'];
                                    $criteriaLabel = ($thisCriteria) ? $view['translator']->trans(
                                        $variants['criteria'][$thisCriteria]['label']
                                    ) : '';
                                else:
                                    $thisCriteria = $criteriaLabel = '';
                                    $weight       = 0;
                                endif;

                                $isPublished = $variant->isPublished();
                                $totalWeight += ($isPublished) ? $weight : 0;
                                $firstCriteria = (!isset($firstCriteria)) ? $thisCriteria : $firstCriteria;
                                $isWinner      = (isset($abTestResults['winners'])
                                    && in_array(
                                        $variant->getId(),
                                        $abTestResults['winners']
                                    )
                                    && $variants['parent']->getVariantStartDate()
                                    && $isPublished);
                                ?>

                                <li class="list-group-item bg-auto bg-light-xs">
                                    <div class="box-layout">
                                        <div class="col-md-8 va-m">
                                            <div class="row">
                                                <div class="col-xs-1">
                                                    <h3>
                                                        <?php echo $view->render(
                                                            'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                                            array(
                                                                'item'  => $variant,
                                                                'model' => 'email',
                                                                'size'  => '',
                                                                'query' => 'size='
                                                            )
                                                        ); ?>
                                                    </h3>
                                                </div>
                                                <div class="col-xs-11">
                                                    <?php if ($isWinner): ?>
                                                        <div class="mr-xs pull-left" data-toggle="tooltip"
                                                             title="<?php echo $view['translator']->trans(
                                                                 'mautic.email.abtest.makewinner'
                                                             ); ?>">
                                                            <a class="btn btn-warning"
                                                               data-toggle="confirmation"
                                                               href="<?php echo $view['router']->path(
                                                                   'mautic_email_action',
                                                                   array(
                                                                       'objectAction' => 'winner',
                                                                       'objectId'     => $variant->getId()
                                                                   )
                                                               ); ?>"
                                                               data-toggle="confirmation"
                                                               data-message="<?php echo $view->escape(
                                                                   $view["translator"]->trans(
                                                                       "mautic.email.abtest.confirmmakewinner",
                                                                       array("%name%" => $variant->getName())
                                                                   )
                                                               ); ?>"
                                                               data-confirm-text="<?php echo $view->escape(
                                                                   $view["translator"]->trans(
                                                                       "mautic.email.abtest.makewinner"
                                                                   )
                                                               ); ?>"
                                                               data-confirm-callback="executeAction"
                                                               data-cancel-text="<?php echo $view->escape(
                                                                   $view["translator"]->trans("mautic.core.form.cancel")
                                                               ); ?>">
                                                                <i class="fa fa-trophy"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <h5 class="fw-sb text-primary">
                                                        <a href="<?php echo $view['router']->path(
                                                            'mautic_email_action',
                                                            array(
                                                                'objectAction' => 'view',
                                                                'objectId'     => $variant->getId()
                                                            )
                                                        ); ?>" data-toggle="ajax"><?php echo $variant->getName(); ?>
                                                            <?php if ($variant->getId() == $email->getId()) : ?>
                                                                <span>[<?php echo $view['translator']->trans(
                                                                        'mautic.core.current'
                                                                    ); ?>]</span>
                                                            <?php endif; ?>
                                                        </a>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 va-t text-right">
                                            <em class="text-white dark-sm">
                                                <?php if ($isPublished
                                                    && ($totalWeight > 100
                                                        || ($thisCriteria
                                                            && $firstCriteria != $thisCriteria))
                                                ): ?>
                                                    <div class="text-danger" data-toggle="label label-danger"
                                                         title="<?php echo $view['translator']->trans(
                                                             'mautic.email.variant.misconfiguration'
                                                         ); ?>">
                                                        <div><span class="badge"><?php echo $weight; ?>%</span></div>
                                                        <div>
                                                            <i class="fa fa-fw fa-exclamation-triangle"></i><?php echo $criteriaLabel; ?>
                                                        </div>
                                                    </div>
                                                <?php elseif ($isPublished && $criteriaLabel): ?>
                                                    <div class="text-success">
                                                        <div><span class="label label-success"><?php echo $weight; ?>
                                                                %</span></div>
                                                        <div>
                                                            <i class="fa fa-fw fa-check"></i><?php echo $criteriaLabel; ?>
                                                        </div>
                                                    </div>
                                                <?php elseif ($thisCriteria): ?>
                                                    <div class="text-muted">
                                                        <div><span class="label label-default"><?php echo $weight; ?>
                                                                %</span></div>
                                                        <div><?php echo $criteriaLabel; ?></div>
                                                    </div>
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
                <div class="panel-title"><?php echo $view['translator']->trans('mautic.email.urlvariant'); ?></div>
            </div>
            <div class="panel-body pt-xs">
                <div class="input-group">
                    <input onclick="this.setSelectionRange(0, this.value.length);" type="text" class="form-control"
                           readonly
                           value="<?php echo $previewUrl; ?>"/>
                <span class="input-group-btn">
                    <button class="btn btn-default btn-nospin"
                            onclick="window.open('<?php echo $previewUrl; ?>', '_blank');">
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
    <input name="entityId" id="entityId" type="hidden" value="<?php echo $email->getId(); ?>"/>
</div>
<!--/ end: box layout -->
<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    array(
        'id'     => 'abStatsModal',
        'header' => false,
        'body'   => (isset($abTestResults['supportTemplate'])) ? $view->render(
            $abTestResults['supportTemplate'],
            array('results' => $abTestResults, 'variants' => $variants)
        ) : $view['translator']->trans('mautic.email.abtest.noresults'),
        'size'   => 'lg'
    )
); ?>
