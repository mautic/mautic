<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$header = $view['translator']->trans(
    'mautic.report.report.header.view',
    ['%name%' => $view['translator']->trans($report->getName())]
);

if ($tmpl == 'index') {
    $showDynamicFilters  = (!empty($report->getSettings()['showDynamicFilters']) === true);
    $hideDateRangeFilter = (!empty($report->getSettings()['hideDateRangeFilter']) === true);

    $view->extend('MauticCoreBundle:Default:content.html.php');
    $view['slots']->set('mauticContent', 'report');

    $view['slots']->set('headerTitle', $header);

    $buttons = [];
    if (!empty($data) || !empty($graphs)) {
        $buttons[] = [
            'attr' => [
                'target'      => '_new',
                'data-toggle' => '',
                'class'       => 'btn btn-default btn-nospin',
                'href'        => $view['router']->path(
                    'mautic_report_export',
                    ['objectId' => $report->getId(), 'format' => 'html']
                ),
            ],
            'btnText'   => $view['translator']->trans('mautic.form.result.export.html'),
            'iconClass' => 'fa fa-file-code-o',
        ];

        if (!empty($data)) {
            $buttons[] = [
                'attr' => [
                    'data-toggle' => 'download',
                    'class'       => 'btn btn-default btn-nospin',
                    'href'        => $view['router']->path(
                        'mautic_report_export',
                        ['objectId' => $report->getId(), 'format' => 'csv']
                    ),
                ],
                'btnText'   => $view['translator']->trans('mautic.form.result.export.csv'),
                'iconClass' => 'fa fa-file-text-o',
            ];

            if (class_exists('PHPExcel')) {
                $buttons[] = [
                    'attr' => [
                        'data-toggle' => 'download',
                        'class'       => 'btn btn-default btn-nospin',
                        'href'        => $view['router']->path(
                            'mautic_report_export',
                            ['objectId' => $report->getId(), 'format' => 'xlsx']
                        ),
                    ],
                    'btnText'   => $view['translator']->trans('mautic.form.result.export.xlsx'),
                    'iconClass' => 'fa fa-file-excel-o',
                ];
            }
        }
    }

    $view['slots']->set(
        'actions',
        $view->render(
            'MauticCoreBundle:Helper:page_actions.html.php',
            [
                'item'            => $report,
                'templateButtons' => [
                    'edit' => $view['security']->hasEntityAccess(
                        $permissions['report:reports:editown'],
                        $permissions['report:reports:editother'],
                        $report->getCreatedBy()
                    ),
                    'delete' => $view['security']->hasEntityAccess(
                        $permissions['report:reports:deleteown'],
                        $permissions['report:reports:deleteother'],
                        $report->getCreatedBy()
                    ),
                    'close' => $view['security']->hasEntityAccess(
                        $permissions['report:reports:viewown'],
                        $permissions['report:reports:viewother'],
                        $report->getCreatedBy()
                    ),
                ],
                'routeBase'         => 'report',
                'langVar'           => 'report.report',
                'postCustomButtons' => $buttons,
            ]
        )
    );

    $view['slots']->set(
        'publishStatus',
        $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $report])
    );
}
?>

<!-- report detail header -->
<?php if ($report->getDescription()): ?>
<div class="pr-md pl-md pt-lg pb-lg">
    <div class="text-white dark-sm mb-0"><?php echo $report->getDescription(); ?></div>
</div>
<?php endif; ?>
<!--/ report detail header -->
<!-- report detail collapseable -->
<div id="report-shelves" class="mb-5" aria-multiselectable="true">
    <div class="collapse" id="report-details">
        <div class="pr-md pl-md pb-md">
            <div class="panel shd-none mb-0">
                <table class="table table-bordered table-striped mb-0">
                    <tbody>
                    <?php echo $view->render(
                        'MauticCoreBundle:Helper:details.html.php',
                        ['entity' => $report]
                    ); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="collapse<?php if ($showDynamicFilters): ?> in<?php endif; ?>" id="report-filters">
        <div class="pr-md pl-md pb-md">
            <div class="panel shd-none mb-0 pa-lg">
                <div class="row">
                    <div class="col-sm-12 mb-10<?php if ($hideDateRangeFilter):?> hide<?php endif; ?>">
                        <?php echo $view->render('MauticCoreBundle:Helper:graph_dateselect.html.php', ['dateRangeForm' => $dateRangeForm]); ?>
                    </div>
                    <?php $view['form']->start($dynamicFilterForm); ?>
                    <?php foreach ($dynamicFilterForm->children as $filter): ?>
                    <?php if ($filter->vars['block_prefixes'][1] == 'hidden') {
                        continue;
                    } ?>
                    <div class="col-sm-4">
                        <?php echo $view['form']->row($filter); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php $view['form']->end($dynamicFilterForm); ?>
                </div>
            </div>
        </div>
    </div>
    <!--/ report detail collapseable -->

    <div class="bg-auto bg-dark-xs">
        <!-- report detail collapseable toggler -->
        <div class="hr-expand nm">
            <a href="#report-details" class="arrow text-muted collapsed" data-toggle="collapse" aria-expanded="false" aria-controls="report-details">
                <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?>
            </a>
            <a href="#report-filters" class="arrow text-muted <?php if (!$showDynamicFilters): ?>collapsed<?php endif; ?>" data-toggle="collapse" aria-expanded="false" aria-controls="report-filters">
                <span class="caret"></span> <?php echo $view['translator']->trans('mautic.core.filters'); ?>
            </a>
        </div>
        <!--/ report detail collapseable toggler -->
    </div>
</div>

<div class="report-content">
    <?php $view['slots']->output('_content'); ?>
</div>
<?php if (!empty($debug)): ?>
<div class="well">
    <h4>Debug: <?php echo $debug['query_time']; ?></h4>
    <div><?php echo $debug['query']; ?></div>
</div>
<?php endif; ?>
<!--/ end: box layout -->
<input type="hidden" name="entityId" id="entityId" value="<?php echo $report->getId(); ?>"/>
