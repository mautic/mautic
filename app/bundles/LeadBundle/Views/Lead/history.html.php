<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!-- form -->
<form action="" class="panel">
    <div class="form-control-icon pa-xs">
        <input type="text" class="form-control bdr-w-0" placeholder="Search...">
        <span class="the-icon fa fa-search text-muted mt-xs"></span><!-- must below `form-control` -->
    </div>
    <?php if (isset($eventTypes) && is_array($eventTypes)) : ?>
    <div class="form-control-icon pa-xs">
        <strong>Filter Events:</strong>
        <?php foreach ($eventTypes as $typeKey => $typeName) : ?>
            <label class="checkbox-inline">
                <input 
                    name="eventFilter"
                    type="checkbox"
                    value="<?php echo $typeKey; ?>"
                    <?php echo in_array($typeKey, $eventFilter) ? 'checked' : ''; ?>
                    onchange="Mautic.refreshLeadTimeline(<?php echo $lead->getId(); ?>, this);">
                <?php echo $typeName; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</form>
<!--/ form -->

<!-- timeline -->
<ul class="timeline">
    <li class="header ellipsis bg-white">Recent Events</li>
    <li class="wrapper">
        <ul class="events">
            <?php foreach ($events as $event) : ?>
                <?php if (isset($event['contentTemplate'])) : ?>
                    <?php echo $view->render($event['contentTemplate'], array('event' => $event)); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </li>
</ul>
<!--/ timeline -->