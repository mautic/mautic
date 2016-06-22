<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index') {
    $view->extend('MauticStageBundle:Stage:index.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered stage-list" id="stageTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'checkall' => 'true',
                        'target'   => '#stageTable'
                    )
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'stage',
                        'orderBy'    => 'p.name',
                        'text'       => 'mautic.core.name',
                        'class'      => 'col-stage-name',
                        'default'    => true
                    )
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'stage',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.core.category',
                        'class'      => 'visible-md visible-lg col-stage-category'
                    )
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    array(
                        'sessionVar' => 'stage',
                        'orderBy'    => 'p.id',
                        'text'       => 'mautic.core.id',
                        'class'      => 'visible-md visible-lg col-stage-id'
                    )
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            array(
                                'item'            => $item,
                                'templateButtons' => array(
                                    'edit'   => $permissions['stage:stages:edit'],
                                    'clone'  => $permissions['stage:stages:create'],
                                    'delete' => $permissions['stage:stages:delete'],
                                ),
                                'routeBase'       => 'stage'
                            )
                        );
                        ?>
                    </td>
                    <td>
                        <div>

                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                array('item' => $item, 'model' => 'stage')
                            ); ?>
                            <a href="<?php echo $view['router']->generate(
                                'mautic_stage_action',
                                array("objectAction" => "edit", "objectId" => $item->getId())
                            ); ?>" data-toggle="ajax">
                                <?php echo $item->getName(); ?>
                            </a>
                        </div>
                        <?php if ($description = $item->getDescription()): ?>
                            <div class="text-muted mt-4">
                                <small><?php echo $description; ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php $category = $item->getCategory(); ?>
                        <?php $catName = ($category)
                            ? $category->getTitle()
                            : $view['translator']->trans(
                                'mautic.core.form.uncategorized'
                            ); ?>
                        <?php $color = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                        <span style="white-space: nowrap;"><span class="label label-default pa-4"
                                                                 style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            array(
                "totalItems" => count($items),
                "page"       => $page,
                "limit"      => $limit,
                "menuLinkId" => 'mautic_stage_index',
                "baseUrl"    => $view['router']->generate('mautic_stage_index'),
                'sessionVar' => 'stage'
            )
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render(
        'MauticCoreBundle:Helper:noresults.html.php',
        array('tip' => 'mautic.stage.action.noresults.tip')
    ); ?>
<?php endif; ?>
