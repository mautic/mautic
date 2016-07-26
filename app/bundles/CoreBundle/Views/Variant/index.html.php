<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($nameGetter)) {
    $nameGetter = 'getName';
}
?>
<?php if (!empty($variants['properties'])): ?>
<?php if ($variants['parent']->getVariantStartDate() != null): ?>
    <div class="box-layout mb-lg">
        <div class="col-xs-10 va-m">
            <h4>
                <?php echo $view['translator']->trans(
                    'mautic.core.variant_start_date',
                    [
                        '%time%' => $view['date']->toTime(
                            $variants['parent']->getVariantStartDate()
                        ),
                        '%date%' => $view['date']->toShort(
                            $variants['parent']->getVariantStartDate()
                        ),
                        '%full%' => $view['date']->toTime(
                            $variants['parent']->getVariantStartDate()
                        ),
                    ]
                ); ?>
            </h4>
        </div>
        <!-- button -->
        <div class="col-xs-2 va-m text-right">
            <a href="#" data-toggle="modal" data-target="#abStatsModal" class="btn btn-primary">
                <?php echo $view['translator']->trans('mautic.core.ab_test.stats'); ?>
            </a>
        </div>
    </div>
<?php endif; ?>
<!--/ header -->

<!-- start: variants list -->
<ul class="list-group">
    <?php if ($variants['parent']) : ?>
        <?php
        $isWinner = (isset($abTestResults['winners'])
            && in_array($variants['parent']->getId(), $abTestResults['winners'])
            && $variants['parent']->getVariantStartDate()
            && $variants['parent']->isPublished());
        $actionUrl = $view['router']->path($actionRoute, ['objectAction' => 'view', 'objectId' => $variants['parent']->getId()]);
        ?>
        <li class="list-group-item bg-auto bg-light-xs">
            <div class="box-layout">
                <div class="col-md-8 va-m">
                    <div class="row">
                        <div class="col-xs-1">
                            <h3>
                                <?php echo $view->render(
                                    'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                    [
                                        'item'  => $variants['parent'],
                                        'model' => $model,
                                        'size'  => '',
                                        'query' => 'size=',
                                    ]
                                ); ?>
                            </h3>
                        </div>
                        <div class="col-xs-11">
                            <?php if ($isWinner): ?>
                            <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.ab_test.parent_winning'); ?>">
                                <a class="btn btn-default disabled" href="javascript:void(0);">
                                    <i class="fa fa-trophy"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                            <h5 class="fw-sb text-primary">
                                <a href="<?php echo $actionUrl ?>" data-toggle="ajax">
                                    <?php echo $variants['parent']->$nameGetter(); ?>
                                    <?php if ($variants['parent']->getId() == $activeEntity->getId()) : ?>
                                    <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                                    <?php endif; ?>
                                    <span>[<?php echo $view['translator']->trans('mautic.core.parent'); ?>]</span>
                                </a>
                            </h5>
                            <?php if (method_exists($variants['parent'], 'getAlias')): ?>
                            <span class="text-white dark-sm"><?php echo $variants['parent']->getAlias(); ?></span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 va-t text-right">
                    <em class="text-white dark-sm">
                        <span class="label label-success">
                            <?php echo (int) $variants['properties'][$variants['parent']->getId()]['weight']; ?>%
                        </span>
                    </em>
                </div>
            </div>
        </li>
    <?php endif; ?>
    <?php $totalWeight = (int) $variants['properties'][$variants['parent']->getId()]['weight']; ?>
    <?php if (count($variants['children'])): ?>
        <?php /** @var \Mautic\CoreBundle\Entity\VariantEntityInterface $variant */ ?>
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

            $isPublished   = $variant->isPublished();
            $totalWeight  += ($isPublished) ? $weight : 0;
            $firstCriteria = (!isset($firstCriteria)) ? $thisCriteria : $firstCriteria;
            $isWinner      = (isset($abTestResults['winners'])
                && in_array(
                    $variant->getId(),
                    $abTestResults['winners']
                )
                && $variants['parent']->getVariantStartDate()
                && $isPublished);
            $actionUrl = $view['router']->path(
                $actionRoute,
                [
                    'objectAction' => 'view',
                    'objectId'     => $variant->getId(),
                ]
            );
            ?>

            <li class="list-group-item bg-auto bg-light-xs">
                <div class="box-layout">
                    <div class="col-md-8 va-m">
                        <div class="row">
                            <div class="col-xs-1">
                                <h3>
                                    <?php echo $view->render(
                                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                        [
                                            'item'  => $variant,
                                            'model' => $model,
                                            'size'  => '',
                                            'query' => 'size=',
                                        ]
                                    ); ?>
                                </h3>
                            </div>
                            <div class="col-xs-11">
                                <?php if ($isWinner): ?>
                                <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans(              'mautic.core.ab_test.make_winner'); ?>">
                                    <a class="btn btn-warning"
                                       data-toggle="confirmation"
                                       href="<?php echo $view['router']->path(
                                           $actionRoute,
                                           [
                                               'objectAction' => 'winner',
                                               'objectId'     => $variant->getId(),
                                           ]
                                       ); ?>"
                                       data-message="<?php echo $view->escape(
                                           $view["translator"]->trans(
                                               "mautic.core.ab_test.confirm_make_winner",
                                               ["%name%" => $variant->$nameGetter()]
                                           )
                                       ); ?>"
                                       data-confirm-text="<?php echo $view->escape(
                                           $view["translator"]->trans(
                                               "mautic.core.ab_test.make_winner"
                                           )
                                       ); ?>"
                                       data-confirm-callback="executeAction"
                                       data-cancel-text="<?php echo $view->escape(
                                           $view["translator"]->trans(
                                               "mautic.core.form.cancel"
                                           )
                                       ); ?>">
                                        <i class="fa fa-trophy"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <h5 class="fw-sb text-primary">
                                    <a href="<?php echo $actionUrl ?>" data-toggle="ajax">
                                        <?php echo $variant->$nameGetter(); ?>
                                        <?php if ($variant->getId() == $activeEntity->getId()) : ?>
                                        <span>[<?php echo $view['translator']->trans('mautic.core.current'); ?>]</span>
                                        <?php endif; ?>
                                    </a>
                                </h5>
                                <?php if (method_exists($variant, 'getAlias')): ?>
                                <span class="text-white dark-sm"><?php echo $variant->getAlias(); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 va-t text-right">
                        <em class="text-white dark-sm">
                            <?php if ($isPublished && ($totalWeight > 100 || ($thisCriteria && $firstCriteria != $thisCriteria))): ?>
                                <div class="text-danger" data-toggle="label label-danger"
                                     title="<?php echo $view['translator']->trans('mautic.core.variant.misconfiguration'); ?>">
                                    <div>
                                        <span class="badge"><?php echo $weight; ?>%</span>
                                    </div>
                                    <div>
                                        <i class="fa fa-fw fa-exclamation-triangle"></i><?php echo $criteriaLabel; ?>
                                    </div>
                                </div>
                            <?php elseif ($isPublished && $criteriaLabel): ?>
                                <div class="text-success">
                                    <div>
                                        <span class="label label-success"><?php echo $weight; ?>%</span>
                                    </div>
                                    <div>
                                        <i class="fa fa-fw fa-check"></i><?php echo $criteriaLabel; ?>
                                    </div>
                                </div>
                            <?php elseif ($thisCriteria): ?>
                                <div class="text-muted">
                                    <div>
                                        <span class="label label-default"><?php echo $weight; ?>%</span>
                                    </div>
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

<?php echo $view->render(
    'MauticCoreBundle:Helper:modal.html.php',
    [
        'id'     => 'abStatsModal',
        'header' => false,
        'body'   => (isset($abTestResults['supportTemplate'])) ? $view->render(
            $abTestResults['supportTemplate'],
            ['results' => $abTestResults, 'variants' => $variants]
        ) : $view['translator']->trans('mautic.core.ab_test.noresults'),
        'size'   => 'lg',
    ]
); ?>
<?php endif; ?>