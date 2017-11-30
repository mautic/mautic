<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Standard:index.html.php');
}

if (!isset($templateVariables)) {
    $templateVariables = [];
}

if (!isset($sessionVar)) {
    $sessionVar = 'entity';
}

if (!isset($nameAction)) {
    $nameAction = 'view';
}

if (count($items)):
    if ($items instanceof \Doctrine\ORM\Tools\Pagination\Paginator):
        $items = $items->getIterator()->getArrayCopy();
    endif;
    $firstItem = reset($items);
    ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered <?php echo $sessionVar; ?>-list">
            <thead>
            <tr>
                <?php
                if (empty($ignoreStandardColumns)):
                    echo $view->render(
                        'MauticCoreBundle:Helper:tableheader.html.php',
                        [
                            'checkall'        => 'true',
                            'actionRoute'     => $actionRoute,
                            'indexRoute'      => $indexRoute,
                            'templateButtons' => [
                                'delete' => !empty($permissions[$permissionBase.':deleteown']) || !empty($permissions[$permissionBase.':deleteown']) || !empty($permissions[$permissionBase.':delete']),
                            ],
                        ]
                    );

                    echo $view->render(
                        'MauticCoreBundle:Helper:tableheader.html.php',
                        [
                            'sessionVar' => $sessionVar,
                            'orderBy'    => $tablePrefix.'.name',
                            'text'       => 'mautic.core.name',
                            'class'      => 'col-name',
                            'default'    => true,
                        ]
                    );

                    if (method_exists($firstItem, 'getCategory')):
                        echo $view->render(
                            'MauticCoreBundle:Helper:tableheader.html.php',
                            [
                                'sessionVar' => $sessionVar,
                                'orderBy'    => (isset($categoryTablePrefix) ? $categoryTablePrefix : 'cat').'.title',
                                'text'       => 'mautic.core.category',
                                'class'      => 'visible-md visible-lg col-focus-category',
                            ]
                        );
                    endif;
                endif;

                if (isset($listHeaders)):
                    foreach ($listHeaders as $header):
                        if (!isset($header['sessionVar'])):
                            $header['sessionVar'] = $sessionVar;
                        endif;

                        echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', $header);
                    endforeach;
                endif;

                if (empty($ignoreStandardColumns)):
                    echo $view->render(
                        'MauticCoreBundle:Helper:tableheader.html.php',
                        [
                            'sessionVar' => $sessionVar,
                            'orderBy'    => $tablePrefix.'.id',
                            'text'       => 'mautic.core.id',
                            'class'      => 'visible-md visible-lg col-id',
                        ]
                    );
                endif;
                ?>
                <?php echo $view['content']->getCustomContent('list.headers', $mauticTemplateVars); ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <?php if (empty($ignoreStandardColumns)): ?>
                        <td>
                            <?php
                            echo $view->render(
                                'MauticCoreBundle:Helper:list_actions.html.php',
                                [
                                    'item'            => $item,
                                    'templateButtons' => [
                                        'edit' => method_exists($item, 'getCreatedBy')
                                            ?
                                            $view['security']->hasEntityAccess(
                                                $permissions[$permissionBase.':editown'],
                                                $permissions[$permissionBase.':editother'],
                                                $item->getCreatedBy()
                                            )
                                            :
                                            $permissions[$permissionBase.':edit'],
                                        'clone'  => isset($enableCloneButton) ? $permissions[$permissionBase.':create'] : false,
                                        'delete' => method_exists($item, 'getCreatedBy')
                                            ?
                                            $view['security']->hasEntityAccess(
                                                $permissions[$permissionBase.':deleteown'],
                                                $permissions[$permissionBase.':deleteother'],
                                                $item->getCreatedBy()
                                            )
                                            :
                                            $permissions[$permissionBase.':delete'],
                                        'abtest' => isset($enableAbTestButton) ? $permissions[$permissionBase.':create'] : false,
                                    ],
                                    'actionRoute'     => $actionRoute,
                                    'indexRoute'      => $indexRoute,
                                    'translationBase' => $translationBase,
                                    'customButtons'   => isset($customButtons) ? $customButtons : [],
                                ]
                            );
                            ?>
                        </td>
                        <td>
                            <div>
                                <?php if (method_exists($item, 'isPublished')): ?>
                                    <?php echo $view->render(
                                        'MauticCoreBundle:Helper:publishstatus_icon.html.php',
                                        ['item' => $item, 'model' => $modelName]
                                    ); ?>
                                <?php endif; ?>
                                <a data-toggle="ajax" href="<?php echo $view['router']->path(
                                    $actionRoute,
                                    ['objectId' => $item->getId(), 'objectAction' => $nameAction]
                                ); ?>">
                                    <?php echo $item->getName(); ?>
                                    <?php echo $view['content']->getCustomContent('list.name', $mauticTemplateVars); ?>
                                </a>
                            </div>
                            <?php if (method_exists($item, 'getDescription') && $description = $item->getDescription()): ?>
                                <div class="text-muted mt-4">
                                    <small><?php echo $description; ?></small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php if (method_exists($item, 'getCategory')): ?>
                            <td class="visible-md visible-lg">
                                <?php $category = $item->getCategory(); ?>
                                <?php $catName  = ($category)
                                    ? $category->getTitle()
                                    : $view['translator']->trans(
                                        'mautic.core.form.uncategorized'
                                    ); ?>
                                <?php $color = ($category) ? '#'.$category->getColor() : 'inherit'; ?>
                                <span style="white-space: nowrap;"><span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: <?php echo $color; ?>;"> </span> <span><?php echo $catName; ?></span></span>
                            </td>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php
                    if (isset($listItemTemplate)):
                        $templateVariables['item'] = $item;
                        echo $view->render($listItemTemplate, $templateVariables);
                    endif;
                    ?>
                    <?php if (empty($ignoreStandardColumns)): ?>
                        <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                    <?php endif; ?>
                    <?php echo $view['content']->getCustomContent('list.columns', $mauticTemplateVars); ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel-footer">
        <?php echo $view->render(
            'MauticCoreBundle:Helper:pagination.html.php',
            [
                'totalItems' => $totalItems,
                'page'       => $page,
                'limit'      => $limit,
                'baseUrl'    => $view['router']->path($indexRoute),
                'sessionVar' => $sessionVar,
            ]
        ); ?>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
