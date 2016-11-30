<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$id = $variant->getId();
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
$actionUrl = $view['router']->path(
    $actionRoute,
    [
        'objectAction' => 'view',
        'objectId'     => $variant->getId(),
    ]
);
$isCurrent = ($variant->getId() === $activeEntity->getId());
?>
<li class="list-group-item bg-auto bg-<?php echo ($isCurrent) ? 'dark' : 'light'; ?>-xs">
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
                        <div class="mr-xs pull-left" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.ab_test.make_winner'); ?>">
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
                                   $view['translator']->trans(
                                       'mautic.core.ab_test.confirm_make_winner',
                                       ['%name%' => $variant->$nameGetter()]
                                   )
                               ); ?>"
                               data-confirm-text="<?php echo $view->escape(
                                   $view['translator']->trans(
                                       'mautic.core.ab_test.make_winner'
                                   )
                               ); ?>"
                               data-confirm-callback="executeAction"
                               data-cancel-text="<?php echo $view->escape(
                                   $view['translator']->trans(
                                       'mautic.core.form.cancel'
                                   )
                               ); ?>">
                                <i class="fa fa-trophy"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    <h5 class="fw-sb text-primary">
                        <a href="<?php echo $actionUrl ?>" data-toggle="ajax">
                            <?php echo $variant->$nameGetter(); ?>
                        </a>
                        <?php if ($isCurrent) : ?>
                            <span class="label label-success"><?php echo $view['translator']->trans('mautic.core.current'); ?></span>
                        <?php endif; ?>
                        <?php if ($variants['parent']->getId() === $variant->getId()) : ?>
                            <span class="label label-warning"><?php echo $view['translator']->trans('mautic.core.parent'); ?></span>
                        <?php endif; ?>
                        <?php if ($variant instanceof \Mautic\CoreBundle\Entity\TranslationEntityInterface && $variant->isTranslation()): ?>
                            <span data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.icon_tooltip.translation'); ?>">
                                <i class="fa fa-fw fa-language"></i>
                            </span>
                        <?php endif; ?>
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
                <?php else: ?>
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
