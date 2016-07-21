<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$viewTime = $duration = $percentage = $unknown = $view['translator']->trans('mautic.core.unknown');

if ($event['extra']['hit']['timeWatched']) {
    $viewTimeActual = $viewTime = $event['extra']['hit']['timeWatched'];

    // format the time
    if ($viewTime > 60) {
        $sec      = $viewTime % 60;
        $min      = floor($viewTime / 60);
        $viewTime = $min . 'm ' . $sec . 's';
    } else {
        $viewTime .= 's';
    }
}

if ($event['extra']['hit']['duration']) {
    $durationActual = $duration = $event['extra']['hit']['duration'];

    // format the time
    if ($duration > 60) {
        $sec      = $duration % 60;
        $min      = floor($duration / 60);
        $duration = $min . 'm ' . $sec . 's';
    } else {
        $duration .= 's';
    }
}

if ($viewTime !== $unknown && $duration !== $unknown) {
    $percentage = round(($viewTimeActual / $durationActual) * 100);
}

$icon = (isset($event['icon'])) ? $event['icon'] : '';

$query = $event['extra']['hit']['query'];

?>

<li class="wrapper page-hit">
    <div class="figure"><span class="fa <?php echo $icon; ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3 class="ellipsis">
                Viewed Video
            </h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', ['%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel']]); ?></p>
        </div>
        <?php if (isset($event['extra'])) : ?>
            <div class="panel-footer">
                <dl class="dl-horizontal">
                    <dt><?php echo $view['translator']->trans('mautic.page.time.on.video'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $view['translator']->trans('mautic.page.time.on.video.value', ['%time_watched%' => $viewTime, '%duration%' => $duration, '%percentage%' => $percentage]); ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.page.referrer'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $event['extra']['hit']['referer'] ? $view['assets']->makeLinks($event['extra']['hit']['referer']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.video.url'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $event['extra']['hit']['url'] ? $view['assets']->makeLinks($event['extra']['hit']['url']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
                </dl>
                <div class="small">
                    <?php echo $event['extra']['hit']['userAgent']; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</li>
