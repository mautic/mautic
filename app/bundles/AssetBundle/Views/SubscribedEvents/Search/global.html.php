<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php if (!empty($showMore)): ?>
<a href="<?php echo $view['router']->generate('mautic_asset_index', ['search' => $searchString]); ?>" data-toggle="ajax">
    <span><?php echo $view->escape($view['translator']->trans('mautic.core.search.more', ['%count%' => $remaining])); ?></span>
</a>
<?php else: ?>
<a href="<?php echo $view['router']->generate('mautic_asset_action', ['objectAction' => 'view', 'objectId' => $asset->getId()]); ?>" data-toggle="ajax">
    <?php echo $view->escape($asset->getTitle()); ?>
    <span class="label label-default pull-right" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.asset.downloadcount'); ?>" data-placement="left">
        <?php echo $asset->getDownloadCount(); ?>
    </span>
</a>
<?php endif; ?>