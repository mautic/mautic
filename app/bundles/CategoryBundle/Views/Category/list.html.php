<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticCategoryBundle:Category:index.html.php');
?>

<?php if (count($items)): ?>
    <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered category-list" id="categoryTable">
                <thead>
                <tr>
                    <th class="visible-md visible-lg col-page-actions pl-20">
                        <div class="checkbox-inline custom-primary">
                            <label class="mb-0 pl-10">
                                <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#categoryTable">
                                <span></span>
                            </label>
                        </div>
                    </th>
                    <?php

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'text'       => '',
                        'class'      => 'col-category-color'
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'orderBy'    => 'c.title',
                        'text'       => 'mautic.category.thead.title',
                        'class'      => 'col-category-title',
                        'default'    => true
                    ));

                    echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                        'sessionVar' => 'category',
                        'orderBy'    => 'c.id',
                        'text'       => 'mautic.category.thead.id',
                        'class'      => 'visible-md visible-lg col-page-id'
                    ));
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="visible-md visible-lg">
                            <?php
                            echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', array(
                                'item'      => $item,
                                'templateButtons' => array(
                                    'edit'      => $permissions[$bundle.':categories:edit'],
                                    'delete'    => $permissions[$bundle.':categories:delete'],
                                ),
                                'editMode'  => 'ajaxmodal',
                                'editAttr'  => array(
                                    'data-target' => '#CategoryFormModal',
                                    'data-ignore-removemodal' => 'true',
                                    'data-header' => $view['translator']->trans('mautic.category.header.edit', array("%name%" => $item->getTitle())),
                                ),
                                'routeBase' => 'category',
                                'query'      => array(
                                    'bundle' => $bundle
                                )
                            ));
                            ?>
                        </td>
                        <td>
                            <span class="label label-default pa-10" style="background: #<?php echo $item->getColor(); ?>;"> </span>
                        </td>
                        <td>
                            <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                                'item'  => $item,
                                'model' => 'category',
                                'query' => 'bundle=' . $bundle
                            )); ?>
                            <?php if ($permissions[$bundle.':categories:edit']): ?>
                            <a href="<?php echo $view['router']->generate('mautic_category_action', array('bundle' => $bundle, 'objectAction' => 'edit', 'objectId' => $item->getId())); ?>" data-toggle="ajaxmodal" data-target="#CategoryFormModal" data-ignore-removemodal="true" data-header="<?php echo $view['translator']->trans('mautic.category.header.edit', array("%name%" => $item->getTitle())); ?>"
                           <?php endif; ?>
                            <span><?php echo $item->getTitle(); ?> (<?php echo $item->getAlias(); ?>)</span>
                            <?php if ($permissions[$bundle.':categories:edit']): ?>
                            </a>
                            <?php endif; ?>
                            <?php if ($description = $item->getDescription()): ?>
                                <div class="text-muted mt-4"><small><?php echo $description; ?></small></div>
                            <?php endif; ?>
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
            "menuLinkId"      => 'mautic_category_index',
            "baseUrl"         => $view['router']->generate('mautic_category_index', array(
                'bundle' => $bundle
            )),
            'sessionVar'      => 'category'
        )); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.category.noresults.tip')); ?>
<?php endif; ?>
