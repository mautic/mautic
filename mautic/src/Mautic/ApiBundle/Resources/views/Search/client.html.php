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
    <div class="gs-client-name">
        <a href="javascript: void(0);" class="pull-right margin-md-sides" onclick="Mautic.loadContent('<?php echo $this->container->get('router')->generate(
            'mautic_client_index', array('filter-client' => $searchString)); ?>');">
            <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
        </a>
    </div>
    <?php else: ?>
    <div class="gs-client-name">
        <?php if ($canEdit): ?>
        <a href="javascript: void(0);" onclick="Mautic.loadContent('<?php echo $this->container->get('router')->generate(
        'mautic_client_action', array('objectAction' => 'edit', 'objectId' => $client->getId())); ?>');">
        <?php endif; ?>
        <span><?php echo $client->getName(); ?></span>
        <?php if ($canEdit): ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>