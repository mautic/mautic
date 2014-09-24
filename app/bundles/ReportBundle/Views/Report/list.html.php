<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticReportBundle:Report:index.html.php');
?>

<div class="panel panel-default page-list">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo $view['translator']->trans('mautic.report.report.header.index'); ?></h3>
    </div>
    <div class="panel-toolbar-wrapper">
        <div class="panel-toolbar">
            <div class="checkbox custom-checkbox pull-left">
                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#reportTable">
                <label for="customcheckbox-one0"><?php echo $view['translator']->trans('mautic.core.table.selectall'); ?></label>
            </div>
        </div>
        <div class="panel-toolbar text-right">
            <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
        </div>
    </div>
    <div class="table-responsive panel-collapse pull out page-list">
        <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
            <thead>
                <tr>
                    <th class="col-page-actions"></th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'report',
                        'orderBy'    => 'p.title',
                        'text'       => 'mautic.report.report.thead.title',
                        'class'      => 'col-page-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'report',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.report.report.thead.id',
                        'class'      => 'col-page-id'
                    ));
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $security->hasEntityAccess(
                                $permissions['report:reports:editown'],
                                $permissions['report:reports:editother'],
                                $item->getCreatedBy()
                            ),
                            'clone'     => $permissions['report:reports:create'],
                            'delete'    => $security->hasEntityAccess(
                                $permissions['report:reports:deleteown'],
                                $permissions['report:reports:deleteother'],
                                $item->getCreatedBy()),
                            'routeBase' => 'report',
                            'menuLink'  => 'mautic_report_index',
                            'langVar'   => 'report.report',
                            'nameGetter' => 'getTitle'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'report.report'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_report_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getTitle(); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
        <?php endif; ?>
        <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_report_index',
            "baseUrl"         => $view['router']->generate('mautic_report_index'),
            'sessionVar'      => 'page'
        )); ?>
        </div>
    </div>
</div>
