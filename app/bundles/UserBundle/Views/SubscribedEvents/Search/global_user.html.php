<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php if (!empty($showMore)): ?>
<a href="<?php echo $this->container->get('router')->generate('mautic_user_index', array('filter-user' => $searchString)); ?>" data-toggle="ajax">
    <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
</a>
<?php else: ?>
<div>
    <div class="pull-left mr-xs img-wrapper" style="width: 36px;">
        <img class="img img-responsive" src="<?php echo $view['gravatar']->getImage($user->getEmail(), '100'); ?>" />
    </div>

    <?php if ($canEdit): ?>
    <a href="<?php echo $this->container->get('router')->generate('mautic_user_action', array('objectAction' => 'edit', 'objectId' => $user->getId())); ?>" data-toggle="ajax">
        <?php echo $user->getName(true); ?>
    </a>
    <?php else: ?>
        <?php echo $user->getName(true); ?>
    <?php endif; ?>

    <div><small><?php echo $user->getPosition(); ?></small></div>

    <div class="clearfix"></div>
</div>
<?php endif; ?>