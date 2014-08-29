<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticPointBundle:Point:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm page-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered point-list">
            <thead>
            <tr>
                <th class="col-point-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'point',
                    'orderBy'    => 'p.name',
                    'text'       => 'mautic.point.thead.name',
                    'class'      => 'col-point-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'page',
                    'orderBy'    => 'r.id',
                    'text'       => 'mautic.point.thead.id',
                    'class'      => 'col-point-id'
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
                                $permissions['point:points:editown'],
                                $permissions['point:points:editother'],
                                $item->getCreatedBy()
                            ),
                            'clone'     => $permissions['point:points:create'],
                            'delete'    => $security->hasEntityAccess(
                                $permissions['point:points:deleteown'],
                                $permissions['point:points:deleteother'],
                                $item->getCreatedBy()),
                            'routeBase' => 'point',
                            'menuLink'  => 'mautic_point_index',
                            'langVar'   => 'point'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'point'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_point_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getName(); ?>
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
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_point_index',
        "baseUrl"         => $view['router']->generate('mautic_point_index'),
        'sessionVar'      => 'point'
    )); ?>
    <div class="footer-margin"></div>
</div>
