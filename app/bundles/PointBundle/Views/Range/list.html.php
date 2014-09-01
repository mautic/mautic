<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticPointBundle:Range:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm page-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered pointrange-list">
            <thead>
            <tr>
                <th class="col-pointrange-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'point',
                    'orderBy'    => 'r.name',
                    'text'       => 'mautic.point.range.thead.name',
                    'class'      => 'col-pointrange-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'point',
                    'orderBy'    => 'r.description',
                    'text'       => 'mautic.point.range.thead.description',
                    'class'      => 'col-pointrange-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'point',
                    'orderBy'    => 'r.id',
                    'text'       => 'mautic.point.range.thead.id',
                    'class'      => 'col-pointrange-id'
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
                                $permissions['point:ranges:editown'],
                                $permissions['point:ranges:editother'],
                                $item->getCreatedBy()
                            ),
                            'clone'     => $permissions['point:ranges:create'],
                            'delete'    => $security->hasEntityAccess(
                                $permissions['point:ranges:deleteown'],
                                $permissions['point:ranges:deleteother'],
                                $item->getCreatedBy()),
                            'routeBase' => 'pointrange',
                            'menuLink'  => 'mautic_pointrange_index',
                            'langVar'   => 'pointrange'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'point.range'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_pointrange_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
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
        "menuLinkId"      => 'mautic_pointrange_index',
        "baseUrl"         => $view['router']->generate('mautic_pointrange_index'),
        'sessionVar'      => 'pointrange'
    )); ?>
    <div class="footer-margin"></div>
</div>
