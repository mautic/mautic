<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/** @var \Gaufrette\Filesystem $connector */
/** @var \MauticAddon\MauticCloudStorageBundle\Integration\CloudStorageIntegration $integration */
if (count($items)): ?>
    <ul>
        <?php if (array_key_exists('dirs', $items)) : ?>
            <?php foreach ($items['dirs'] as $item) : ?>
                <li>
                    <a href="#" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>', '/<?php echo rtrim($item, '/'); ?>');">
                        <?php echo $item; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php foreach ($items['keys'] as $item) : ?>
                <li>
                    <a href="#" onclick="Mautic.selectRemoteFile('<?php echo $integration->getPublicUrl($item); ?>');">
                        <?php echo $item; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <?php foreach ($items as $item) : ?>
                <?php if ($connector->getAdapter()->isDirectory($item)) : ?>
                    <li>
                        <a href="#" onclick="Mautic.updateRemoteBrowser('<?php echo $integration->getName(); ?>', '/<?php echo rtrim($item, '/'); ?>');">
                            <?php echo $item; ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li>
                        <a href="#" onclick="Mautic.selectRemoteFile('<?php echo $integration->getPublicUrl($item); ?>');">
                            <?php echo $item; ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>
