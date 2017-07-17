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
<a href="<?php echo $view['router']->generate('mautic_user_index', ['filter-user' => $searchString]); ?>" data-toggle="ajax">
    <span><?php echo $view['translator']->trans('mautic.core.search.more', ['%count%' => $remaining]); ?></span>
</a>
<?php else: ?>
<div>
    <span class="pull-left pr-xs pt-xs" style="width:36px">
        <span class="img-wrapper img-rounded"><img src="<?php echo $view['gravatar']->getImage($user->getEmail(), '100'); ?>" /></span>
    </span>
    <?php if ($canEdit): ?>
    <a href="<?php echo $view['router']->generate('mautic_user_action', ['objectAction' => 'edit', 'objectId' => $user->getId()]); ?>" data-toggle="ajax">
        <?php echo $user->getName(true); ?>
    </a>
    <?php else: ?>
        <?php echo $user->getName(true); ?>
    <?php endif; ?>

    <div><small><?php echo $user->getPosition(); ?></small></div>
    <div class="clearfix"></div>
</div>
<?php endif; ?>