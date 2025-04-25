<div class="page-list">
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered dwctable-list" id="dwcVariationsTable">
            <thead>
                <tr>
                    <?php
                    echo $view->render(
                        'MauticCoreBundle:Helper:tableheader.html.php',
                        [
                            'checkall'        => 'true',
                            'target'          => '#dwcVariationsTable',
                            'routeBase'       => 'dynamicContent',
                            'actionRoute'     => 'mautic_dynamicContent_action',
                            'templateButtons' => [
                                'delete' => $permissions['dynamiccontent:dynamiccontents:deleteown']
                                    || $permissions['dynamiccontent:dynamiccontents:deleteother'],
                            ],
                        ]
                    );

                    ?>
                    <th class="col-dwc-order">
                        <span><?php echo $view['translator']->trans('mautic.dynamicContent.variations.order'); ?></span>
                    </th>
                    <th class="col-dwc-name">
                        <span><?php echo $view['translator']->trans('mautic.core.name'); ?></span>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($variations as $variation): ?>
                <tr<?php echo ($variation->getId() == $entity->getId()) ? ' class="info"' : ''; ?>>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $variation,
                                'templateButtons' => [
                                    'edit' => $view['security']->hasEntityAccess(
                                        $permissions['dynamiccontent:dynamiccontents:editown'],
                                        $permissions['dynamiccontent:dynamiccontents:editother'],
                                        $variation->getCreatedBy()
                                    ),
                                    'clone'  => $permissions['dynamiccontent:dynamiccontents:create'],
                                    'delete' => $view['security']->hasEntityAccess(
                                        $permissions['dynamiccontent:dynamiccontents:deleteown'],
                                        $permissions['dynamiccontent:dynamiccontents:deleteother'],
                                        $variation->getCreatedBy()
                                    ),
                                ],
                                'routeBase'       => 'dynamicContent',
                                'nameGetter'      => 'getName',
                                'translationBase' => 'mautic.dynamicContent',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <?php if ($variation->getId() == $entity->getId()): ?>
                            <?php echo $variation->getDisplayOrder(); ?>
                        <?php else: ?>
                            <a href="<?php echo $view['router']->url(
                                'mautic_dynamicContent_action',
                                ['objectAction' => 'view', 'objectId' => $variation->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $variation->getDisplayOrder(); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($variation->getId() == $entity->getId()): ?>
                            <span class="name"> <?php echo $variation->getName(); ?> </span>
                            <span class="label label-info">
                                <?php echo $view['translator']->trans('mautic.core.current'); ?>
                            </span>
                        <?php else: ?>
                            <a href="<?php echo $view['router']->url(
                                'mautic_dynamicContent_action',
                                ['objectAction' => 'view', 'objectId' => $variation->getId()]
                            ); ?>" data-toggle="ajax">
                                <?php echo $variation->getName(); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
