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
<?php if (count($items)): ?>
<div class="panel panel-default page-list bdr-t-wdh-0">
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <?php echo $view->render('MauticCoreBundle:Helper:search.html.php'); ?>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-warning"><i class="fa fa-files-o"></i></button>
                <button type="button" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
            <thead>
                <tr>
                    <th class="col-page-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1">
                                <span></span>
                            </label>
                        </div>
                    </th>
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
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>