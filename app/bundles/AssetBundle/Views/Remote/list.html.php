<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/** @var \Gaufrette\Filesystem $connector */
if (count($items)): ?>
    <ul>
        <?php foreach ($items as $item) : ?>
            <?php if ($connector->getAdapter()->isDirectory($item)) : ?>
                <li>
                    <!-- <a href="#" onclick="Mautic.updateRemoteBrowser('<?php //echo $integration->getName(); ?>');"> -->
                        <?php echo $item; ?>
                    <!-- </a> -->
                </li>
            <?php else : ?>
                <li>
                    <!-- <a href="#" onclick="Mautic.selectRemoteFile('<?php //echo ''; ?>');"> -->
                        <?php echo $item; ?>
                    <!-- </a> -->
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php', array('tip' => 'mautic.asset.noresults.tip')); ?>
<?php endif; ?>
