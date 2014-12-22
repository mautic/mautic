<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticPointBundle:Trigger:index.html.php');
?>

<?php if (count($items)): ?>
<div class="table-responsive">
    <table class="table table-hover table-striped table-bordered pointtrigger-list" id="triggerTable">
        <thead>
        <tr>
            <th class="col-pointtrigger-actions pl-20">
                <div class="checkbox-inline custom-primary">
                    <label class="mb-0 pl-10">
                        <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#triggerTable">
                        <span></span>
                    </label>
                </div>
            </th>
            <?php
            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'pointtrigger',
                'orderBy'    => 't.name',
                'text'       => 'mautic.point.trigger.thead.name',
                'class'      => 'col-pointtrigger-name',
                'default'    => true
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'pointtrigger',
                'orderBy'    => 'c.title',
                'text'       => 'mautic.point.trigger.thead.category',
                'class'      => 'col-pointtrigger-category'
            ));

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'pointtrigger',
                'orderBy'    => 't.points',
                'text'       => 'mautic.point.trigger.thead.points',
                'class'      => 'col-pointtrigger-points'
            ));

            echo "<th class='col-pointtrigger-color'>" . $view['translator']->trans('mautic.point.trigger.thead.color') . '</th>';

            echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                'sessionVar' => 'pointtrigger',
                'orderBy'    => 't.id',
                'text'       => 'mautic.point.trigger.thead.id',
                'class'      => 'col-pointtrigger-id'
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
                            'edit'      => $permissions['point:triggers:edit'],
                            'clone'     => $permissions['point:triggers:create'],
                            'delete'    => $permissions['point:triggers:delete'],
                        ),
                        'routeBase' => 'pointtrigger',
                        'langVar'   => 'point.trigger'
                    ));
                    ?>
                </td>
                <td>
                    <div>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'point.trigger'
                        )); ?>
                        <?php if ($permissions['point:triggers:edit']): ?>
                        <a href="<?php echo $view['router']->generate('mautic_pointtrigger_action', array("objectAction" => "edit", "objectId" => $item->getId())); ?>" data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                        <?php else: ?>
                        <?php echo $item->getName(); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($description = $item->getDescription()): ?>
                        <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                    <?php endif; ?>
                </td>
                <td class="visible-md visible-lg">
                    <?php $category = $item->getCategory(); ?>
                    <?php $catName  = ($category) ? $category->getTitle() : $view['translator']->trans('mautic.core.form.uncategorized'); ?>
                    <?php $color    = ($category) ? '#' . $category->getColor() : 'inherit'; ?>
                    <span class="label label-default pa-5" style="background: <?php echo $color; ?>;"> </span>
                    <span><?php echo $catName; ?></span>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getPoints(); ?></td>
                <?php
                $color = $item->getColor();
                $colorStyle = ($color) ? ' style="background-color: ' . $color . '"' : '';
                ?>
                <td<?php echo $colorStyle; ?> class="visible-md visible-lg"></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="panel-footer">
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_pointtrigger_index',
        "baseUrl"         => $view['router']->generate('mautic_pointtrigger_index'),
        'sessionVar'      => 'pointtrigger'
    )); ?>
</div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.point.trigger.noresults.tip')); ?>
<?php endif; ?>
