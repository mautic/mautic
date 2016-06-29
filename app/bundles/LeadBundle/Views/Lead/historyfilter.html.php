<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!-- filter form -->

    <form action="" class="panel" id="timeline-filters">
        <div class="form-control-icon pa-xs">
            <input type="text" class="form-control bdr-w-0" name="search" id="search" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>" value="<?php echo $eventFilters['search']; ?>">
            <span class="the-icon fa fa-search text-muted mt-xs"></span>
        </div>
        <?php if (isset($eventTypes) && is_array($eventTypes)) : ?>
            <div class="history-search panel-footer text-muted">
                <div class="col-sm-6">
                    <select name="includeEvents[]" multiple="multiple" class="form-control bdr-w-0" data-placeholder="<?php echo $view['translator']->trans('mautic.lead.lead.filter.bundles.include.placeholder'); ?>">
                        <?php foreach ($eventTypes as $typeKey => $typeName) : ?>
                            <option value="<?php echo $typeKey; ?>"<?php echo in_array($typeKey, $eventFilters['includeEvents']) ? ' selected' : ''; ?> >
                                <?php echo $typeName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <select name="excludeEvents[]" multiple="multiple" class="form-control bdr-w-0" data-placeholder="<?php echo $view['translator']->trans('mautic.lead.lead.filter.bundles.exclude.placeholder'); ?>">
                        <?php foreach ($eventTypes as $typeKey => $typeName) : ?>
                            <option value="<?php echo $typeKey; ?>"<?php echo in_array($typeKey, $eventFilters['excludeEvents']) ? ' selected' : ''; ?> >
                                <?php echo $typeName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <input type="hidden" name="leadId" id="leadId" value="<?php echo $lead->getId(); ?>" />
    </form>

<!--/ filter form -->
