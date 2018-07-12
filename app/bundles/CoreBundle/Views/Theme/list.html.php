<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
// if ($tmpl == 'index')
$view->extend('MauticCoreBundle:Theme:index.html.php');
?>
<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered theme-list" id="themeTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall'        => 'true',
                        'target'          => '#themeTable',
                        'langVar'         => 'core.theme',
                        'routeBase'       => 'themes',
                        'templateButtons' => [
                            'delete' => $permissions['core:themes:delete'],
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'text' => 'mautic.core.title',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'text' => 'mautic.core.author',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'text' => 'mautic.core.features',
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $k => $item): ?>
                <?php if (!empty($item['config']['onlyForBC'])) {
                    continue;
                } ?>
                <?php $thumbnailUrl = $view['assets']->getUrl('themes/'.$k.'/thumbnail.png'); ?>
                <?php $hasThumbnail = file_exists($item['dir'].'/thumbnail.png'); ?>
                <tr>
                    <td>
                        <?php
                        $item['id']            = $item['key'];
                        $previewButtonSettings = [
                            'attr' => [
                                'data-toggle' => 'modal',
                                'data-target' => '#theme-'.$k,
                            ],
                            'btnText'   => $view['translator']->trans('mautic.asset.asset.preview'),
                            'iconClass' => 'fa fa-image',
                        ];
                        $previewButton   = $hasThumbnail ? $previewButtonSettings : [];
                        $deleteButton    = ['delete' => $permissions['core:themes:delete']];
                        $templateButtons = !in_array($k, $defaultThemes) ? $deleteButton : [];
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item'            => $item,
                                'templateButtons' => $templateButtons,
                                'routeBase'       => 'themes',
                                'langVar'         => 'core.theme',
                                'customButtons'   => [
                                    [
                                        'attr' => [
                                            'href' => $view['router']->path(
                                                'mautic_themes_action',
                                                ['objectAction' => 'download', 'objectId' => $k]
                                            ),
                                            'data-toggle' => '0',
                                        ],
                                        'btnText'   => $view['translator']->trans('mautic.core.download'),
                                        'iconClass' => 'fa fa-download',
                                    ],
                                    $previewButton,
                                ],
                            ]
                        );
                        ?>
                        <?php if ($hasThumbnail) : ?>
                            <!-- Modal -->
                            <div class="modal fade" id="theme-<?php echo $k; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $k; ?>">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="<?php echo $k; ?>"><?php echo $view->escape($item['name']); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <div style="background-image: url(<?php echo $thumbnailUrl ?>);background-repeat:no-repeat;background-size:contain; background-position:center; width: 100%; height: 600px"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div>
                            <?php echo $view->escape($item['name']); ?> (<?php echo $view->escape($item['key']); ?>)
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php if (isset($item['config']['authorUrl'])) : ?>
                                <a href="<?php echo $item['config']['authorUrl']; ?>" target="_blank">
                                    <?php echo $view->escape($item['config']['author']); ?>
                                </a>
                            <?php elseif (isset($item['config']['author'])) : ?>
                                <?php echo $view->escape($item['config']['author']); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php if (!empty($item['config']['features'])) : ?>
                            <?php foreach ($item['config']['features'] as $feature) : ?>
                                <span style="white-space: nowrap;">
                                    <span class="label label-default pa-4">
                                        <?php echo $view['translator']->trans('mautic.core.theme.feature.'.$view->escape($feature)); ?>
                                    </span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['tip' => 'mautic.theme.noresults.tip']); ?>
<?php endif; ?>
