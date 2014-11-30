<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticReportBundle:Report:index.html.php');
?>
<?php if (count($items)): ?>
    <div class="table-responsive panel-collapse pull out page-list">
        <table class="table table-hover table-striped table-bordered report-list" id="reportTable">
            <thead>
                <tr>
                    <th class="col-report-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#reportTable">
                                <span></span>
                            </label>
                        </div>
                    </th>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'report',
                        'orderBy'    => 'p.title',
                        'text'       => 'mautic.report.report.thead.title',
                        'class'      => 'col-report-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'report',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.report.report.thead.id',
                        'class'      => 'col-report-id'
                    ));
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                            'item'      => $item,
                            'templateButtons' => array(
                                'edit'      => $security->hasEntityAccess($permissions['report:reports:editown'], $permissions['report:reports:editother'], $item->getCreatedBy()),
                                'clone'     => $permissions['report:reports:create'],
                                'delete'    => $security->hasEntityAccess($permissions['report:reports:deleteown'], $permissions['report:reports:deleteother'], $item->getCreatedBy()),
                            ),
                            'routeBase'  => 'report',
                            'langVar'    => 'report.report',
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
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
