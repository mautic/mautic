<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/** @var \Gaufrette\Filesystem $connector */
/** @var \MauticPlugin\MauticCloudStorageBundle\Integration\CloudStorageIntegration $integration */
if (count($items)): ?>
    <div class="panel panel-primary mb-0">
        <div class="panel-body">
            <input type='text' class='remote-file-search form-control mb-lg' autocomplete='off' placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" />

            <div class="list-group remote-file-list">
                <?php if (array_key_exists('dirs', $items)) : ?>
                    <?php foreach ($items['dirs'] as $item) : ?>
                        <a class="list-group-item" href="#" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>', '/<?php echo rtrim($item, '/'); ?>');">
                            <?php echo $item; ?>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($items['keys'] as $item) : ?>
                        <a class="list-group-item" href="#" onclick="Mautic.selectRemoteFile('<?php echo $integration->getPublicUrl($item); ?>');">
                            <?php echo $item; ?>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <?php if ($connector->getAdapter()->isDirectory($item)) : ?>
                            <a class="list-group-item" href="#" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>', '/<?php echo rtrim($item, '/'); ?>');">
                                <?php echo $item; ?>
                            </a>
                        <?php else : ?>
                            <a class="list-group-item" href="#" onclick="Mautic.selectRemoteFile('<?php echo $integration->getPublicUrl($item); ?>');">
                                <?php echo $item; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', ['message' => 'mautic.asset.remote.no_results']); ?>
<?php endif; ?>
