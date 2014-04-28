<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="global-search-result">
    <?php if (!empty($showMore)): ?>
    <div class="gs-user-profile">
        <a class="pull-right margin-md-sides" href="<?php echo $this->container->get('router')->generate(
            'mautic_user_index', array('filter-user' => $searchString)); ?>"
            data-toggle="ajax">
            <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
        </a>
    </div>
    <?php else: ?>
    <div class="gs-user-avatar">
        <img class="img img-responsive img-thumbnail"
             src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($user->getEmail()))); ?>?s=25" />
    </div>
    <div class="gs-user-profile">
        <?php if ($canEdit): ?>
        <a href="<?php echo $this->container->get('router')->generate(
        'mautic_user_action', array('objectAction' => 'edit', 'objectId' => $user->getId())); ?>"
            data-toggle="ajax">
        <?php endif; ?>
        <span class="gs-user-name"><?php echo $user->getName(true); ?></span>
        <?php if ($canEdit): ?>
        </a>
        <?php endif; ?>
        <span class="gs-user-position"><?php echo $user->getPosition(); ?></span>
    </div>
    <?php endif; ?>
</div>