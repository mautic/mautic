<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="global-search-result">
<?php if (!empty($showMore)): ?>
    <a class="pull-right margin-md-sides" href="<?php echo $this->container->get('router')->generate(
        'mautic_lead_index', array('search' => $searchString)); ?>"
       data-toggle="ajax">
        <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
    </a>
<?php else: ?>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_lead_action', array('objectAction' => 'view', 'objectId' => $lead->getId())); ?>"
    data-toggle="ajax">
        <span class="gs-lead-primary-identifier"><?php echo $lead->getPrimaryIdentifier(true); ?></span>
        <span class="gs-lead-secondary-identifier"><?php echo $lead->getSecondaryIdentifier(); ?></span>
        <span class="badge alert-success gs-count-badge" data-toggle="tooltip" data-placement="left"
             title="<?php echo $view['translator']->trans('mautic.lead.lead.pointscount'); ?>">
        <?php echo $lead->getPoints(); ?>
        </span>
    </a>
<?php endif; ?>
</div>