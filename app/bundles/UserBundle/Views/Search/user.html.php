<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$status = $user->getPublishStatus();
switch ($status) {
    case 'published':
        $icon = " fa-check-circle-o text-success";
        $text = $view['translator']->trans('mautic.core.form.published');
        break;
    case 'unpublished':
        $icon = " fa-times-circle-o text-danger";
        $text = $view['translator']->trans('mautic.core.form.unpublished');
        break;
}
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
    <div class="gs-user-profile">
        <?php if ($canEdit): ?>
        <a href="<?php echo $this->container->get('router')->generate(
        'mautic_user_action', array('objectAction' => 'edit', 'objectId' => $user->getId())); ?>"
            data-toggle="ajax">
        <?php endif; ?>
        <span class="global-search-primary">
            <i class="fa fa-fw fa-lg <?php echo $icon; ?> global-search-publish-status"
               data-toggle="tooltip"
               data-container="body"
               data-placement="right"
               data-status="<?php echo $status; ?>"
               data-original-title="<?php echo $text ?>"></i>
            <?php echo $user->getName(true); ?>
        </span>
        <?php if ($canEdit): ?>
        </a>
        <?php endif; ?>
        <span class="global-search-secondary global-search-indent"><?php echo $user->getPosition(); ?></span>
    </div>
    <div class="gs-user-avatar">
        <img class="img img-responsive img-thumbnail"
             src="<?php echo \Mautic\SocialBundle\Helper\GravatarHelper::getGravatar($user->getEmail(), '25'); ?>" />
    </div>
    <div class="clearfix"></div>
    <?php endif; ?>
</div>