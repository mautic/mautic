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
    <div class="gs-role-name">
        <a class="pull-right margin-md-sides" href="<?php echo $this->container->get('router')->generate(
            'mautic_role_index', array('filter-role' => $searchString)); ?>"
            data-toggle="ajax">
            <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
        </a>
    </div>
    <?php else: ?>
    <div class="gs-role-name">
        <?php if ($canEdit): ?>
        <a href="javascript: void(0);" onclick="Mautic.loadContent('<?php echo $this->container->get('router')->generate(
        'mautic_role_action', array('objectAction' => 'edit', 'objectId' => $role->getId())); ?>');">
        <?php endif; ?>
        <span><?php echo $role->getName(); ?></span>
        <?php if ($canEdit): ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>