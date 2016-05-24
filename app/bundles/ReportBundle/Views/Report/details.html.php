<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$header = $view['translator']->trans(
    'mautic.report.report.header.view',
    array('%name%' => $view['translator']->trans($report->getName()))
);

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
    $view['slots']->set('mauticContent', 'report');

    $view['slots']->set("headerTitle", $header);

    $buttons = array();
    if (!empty($data) || !empty($graphs)) {
        $buttons[] = array(
            'attr'      => array(
                'target'      => '_new',
                'data-toggle' => '',
                'class'       => 'btn btn-default btn-nospin',
                'href'        => $view['router']->generate(
                    'mautic_report_export',
                    array('objectId' => $report->getId(), 'format' => 'html')
                )
            ),
            'btnText'   => $view['translator']->trans('mautic.form.result.export.html'),
            'iconClass' => 'fa fa-file-code-o'
        );

        if (!empty($data)) {
            $buttons[] = array(
                'attr'      => array(
                    'data-toggle' => 'download',
                    'class'       => 'btn btn-default btn-nospin',
                    'href'        => $view['router']->generate(
                        'mautic_report_export',
                        array('objectId' => $report->getId(), 'format' => 'csv')
                    )
                ),
                'btnText'   => $view['translator']->trans('mautic.form.result.export.csv'),
                'iconClass' => 'fa fa-file-text-o'
            );

            if (class_exists('PHPExcel')) {
                $buttons[] = array(
                    'attr'      => array(
                        'data-toggle' => 'download',
                        'class'       => 'btn btn-default btn-nospin',
                        'href'        => $view['router']->generate(
                            'mautic_report_export',
                            array('objectId' => $report->getId(), 'format' => 'xlsx')
                        )
                    ),
                    'btnText'   => $view['translator']->trans('mautic.form.result.export.xlsx'),
                    'iconClass' => 'fa fa-file-excel-o'
                );
            }
        }
    }

    $view['slots']->set(
        'actions',
        $view->render(
            'MauticCoreBundle:Helper:page_actions.html.php',
            array(
                'item'              => $report,
                'templateButtons'   => array(
                    'edit'   => $security->hasEntityAccess(
                        $permissions['report:reports:editown'],
                        $permissions['report:reports:editother'],
                        $report->getCreatedBy()
                    ),
                    'delete' => $security->hasEntityAccess(
                        $permissions['report:reports:deleteown'],
                        $permissions['report:reports:deleteother'],
                        $report->getCreatedBy()
                    ),
                    'close'  => $security->hasEntityAccess(
                        $permissions['report:reports:viewown'],
                        $permissions['report:reports:viewother'],
                        $report->getCreatedBy()
                    )
                ),
                'routeBase'         => 'report',
                'langVar'           => 'report.report',
                'postCustomButtons' => $buttons
            )
        )
    );

    $view['slots']->set(
        'publishStatus',
        $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', array('entity' => $report))
    );
}
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- report detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-10 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $report->getDescription(); ?></div>
                    </div>
                </div>
            </div>
            <!--/ report detail header -->
            <!-- report detail collapseable -->
            <div class="collapse" id="report-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                array('entity' => $report)
                            ); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ report detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- report detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#report-details"><span class="caret"></span> <?php echo $view['translator']->trans(
                            'mautic.core.details'
                        ); ?></a>
                </span>
            </div>
            <!--/ report detail collapseable toggler -->
        </div>

        <div class="report-content">
            <?php $view['slots']->output('_content'); ?>
        </div>
    </div>
    <!--/ left section -->
</div>
<!--/ end: box layout -->
<input type="hidden" name="entityId" id="entityId" value="<?php echo $report->getId(); ?>"/>
