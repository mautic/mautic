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
<a href="<?php echo $view['router']->generate('mautic_client_index', ['search' => $searchString]); ?>" data-toggle="ajax">
    <span><?php echo $view['translator']->trans('mautic.core.search.more', ['%count%' => $remaining]); ?></span>
</a>
<?php else: ?>
<?php if ($canEdit): ?>
<a href="<?php echo $view['router']->generate('mautic_client_action', ['objectAction' => 'edit', 'objectId' => $client->getId()]); ?>" data-toggle="ajax">
    <?php echo $client->getName(); ?>
</a>
<?php else: ?>
<span><?php echo $client->getName(); ?></span>
<?php endif; ?>
<?php endif; ?>