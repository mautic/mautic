<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($nameGetter)) {
    $nameGetter = 'getName';
}
$totalWeight = 0;
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
    <?php
    if ($variants['parent']) :
        echo $view->render('MauticCoreBundle:Variant:row.html.php',
            [
                'totalWeight'   => &$totalWeight,
                'variant'       => $variants['parent'],
                'variants'      => $variants,
                'abTestResults' => $abTestResults,
                'actionRoute'   => $actionRoute,
                'activeEntity'  => $activeEntity,
                'model'         => $model,
                'nameGetter'    => $nameGetter,
            ]
        );
    endif;
    if (count($variants['children'])):
        foreach ($variants['children'] as $id => $variant) :
            echo $view->render('MauticCoreBundle:Variant:row.html.php',
                [
                    'totalWeight'   => &$totalWeight,
                    'variant'       => $variant,
                    'variants'      => $variants,
                    'abTestResults' => $abTestResults,
                    'actionRoute'   => $actionRoute,
                    'activeEntity'  => $activeEntity,
                    'model'         => $model,
                    'nameGetter'    => $nameGetter,
                ]
            );
        endforeach;
    endif;
    ?>
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
        'size' => 'lg',
    ]
); ?>
<?php endif; ?>