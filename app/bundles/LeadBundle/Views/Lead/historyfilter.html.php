<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!-- filter form -->
<form action="" class="panel" id="timeline-filters">
    <div class="form-control-icon pa-xs">
        <input type="text" class="form-control bdr-w-0" name="search" id="search" placeholder="<?php echo $view['translator']->trans('mautic.core.search.placeholder'); ?>">
        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
    </div>
    <?php if (isset($eventTypes) && is_array($eventTypes)) : ?>
        <div class="panel-footer text-muted">
            <?php foreach ($eventTypes as $typeKey => $typeName) : ?>
            <?php
                $typeHelper = explode('.', $typeKey);
                $bundleName = $typeHelper[0];
                if ($bundleName == 'point') $bundleName = 'points';
                if ($bundleName == 'campaign') $bundleName = 'campaigns';
            ?>
            <div class="checkbox-inline custom-primary">
                <label class="mb-0">
                    <input name="eventFilters[]" type="checkbox" value="<?php echo $typeKey; ?>"<?php echo in_array($typeKey, $eventFilter) ? ' checked' : ''; ?> />
                    <span class="mr-0"></span>
                    <i class="fa <?php echo isset($icons[$bundleName]) ? $icons[$bundleName] : 'page'; ?>"></i>
                    <?php echo $typeName; ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <input type="hidden" name="leadId" id="leadId" value="<?php echo $lead->getId(); ?>" />
</form>
<!--/ filter form -->
