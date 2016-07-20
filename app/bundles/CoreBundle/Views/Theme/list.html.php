<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'checkall' => 'true',
                    'target'   => '#themeTable'
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'text'     => 'mautic.core.title',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'text'     => 'mautic.core.author',
                ]);

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
                    'text'     => 'mautic.core.features',
                ]);
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $k => $item): ?>
                <?php if(!empty($item['config']['onlyForBC'])) continue; ?>
                <?php $thumbnailUrl = $view['assets']->getUrl('themes/'.$k.'/thumbnail.png'); ?>
                <?php $hasThumbnail = file_exists($item['dir'].'/thumbnail.png'); ?>
                <tr>
                    <td>
                        <?php
                        $item['id'] = $item['key'];
                        echo $view->render('MauticCoreBundle:Helper:list_actions.html.php', [
                            'item'       => $item,
                            'templateButtons' => [
                                'delete'     => $permissions['core:themes:delete'],
                            ],
                            'routeBase'  => 'themes',
                            'langVar'    => 'core.theme',
                            'customButtons' => $hasThumbnail ? [
                                [
                                    'attr' => [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#theme-'.$k
                                    ],
                                    'btnText'   => $view['translator']->trans('mautic.asset.asset.preview'),
                                    'iconClass' => 'fa fa-image'
                                ]
                            ] : []
                        ]);
                        ?>
                        <?php if ($hasThumbnail) : ?>
                            <!-- Modal -->
                            <div class="modal fade" id="theme-<?php echo $k; ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo $k; ?>">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="<?php echo $k; ?>"><?php echo $item['name']; ?></h4>
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
                            <a href="<?php echo $view['router']->path('mautic_themes_action',
                                ["objectAction" => "view", "objectId" => $item['key']]); ?>"
                                data-toggle="ajax">
                                <?php echo $item['name']; ?> (<?php echo $item['key']; ?>)
                            </a>
                        </div>
                    </td>
                    <td>
                        <div>
                        <?php if (isset($item['config']['authorUrl'])) : ?>
                            <a href="<?php echo $view['router']->path('mautic_themes_action',
                                ["objectAction" => "view", "objectId" => $item['key']]); ?>"
                                data-toggle="ajax">
                                <?php echo $item['config']['author']; ?>
                            </a>
                        <?php elseif(isset($item['config']['author'])) : ?>
                            <?php echo $item['config']['author']; ?>
                        <?php endif; ?>
                        </div>
                    </td>
                    <td class="visible-md visible-lg">
                        <?php if (!empty($item['config']['features'])) : ?>
                            <?php foreach ($item['config']['features'] as $feature) : ?>
                                <span style="white-space: nowrap;">
                                    <span class="label label-default pa-4" style="border: 1px solid #d5d5d5; background: #666;">
                                        <?php echo $feature; ?>
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
