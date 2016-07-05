<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['page'];
$timeOnPage = $view['translator']->trans('mautic.core.unknown');
if ($event['extra']['hit']['dateLeft']) {
    $timeOnPage = ($event['extra']['hit']['dateLeft']->getTimestamp() - $event['extra']['hit']['dateHit']->getTimestamp());

    // format the time
    if ($timeOnPage > 60) {
        $sec = $timeOnPage % 60;
        $min = floor($timeOnPage / 60);
        $timeOnPage = $min . 'm ' . $sec . 's';
    } else {
        $timeOnPage .= 's';
    }
}

$icon = (isset($event['icon'])) ? $event['icon'] : '';

$query = $event['extra']['hit']['query'];

?>

<li class="wrapper page-hit">
    <div class="figure"><span class="fa <?php echo $icon; ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3 class="ellipsis">
                <?php if (!empty($title)): ?>
                    <?php echo $title; ?>
                <?php elseif ($event['extra']['hit']['page_id']) : ?>
                    <a href="<?php echo $view['router']->path('mautic_page_action',
                        array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                       data-toggle="ajax">
                        <?php echo $item->getTitle(); ?>
                    </a>
                <?php elseif (!empty($event['extra']['hit']['urlTitle'])): ?>
                    <a href="<?php echo $event['extra']['hit']['url']; ?>" target="_blank">
                        <?php echo $event['extra']['hit']['urlTitle']; ?>
                    </a>
                <?php else: ?>
                    <?php echo $view['assets']->makeLinks($event['extra']['hit']['url']); ?>
                <?php endif; ?>
            </h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>
        <?php if (isset($event['extra'])) : ?>
            <div class="panel-footer">
                <dl class="dl-horizontal">
                    <dt><?php echo $view['translator']->trans('mautic.page.time.on.page'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $timeOnPage; ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.page.referrer'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $event['extra']['hit']['referer'] ? $view['assets']->makeLinks($event['extra']['hit']['referer']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.page.url'); ?>:</dt>
                    <dd class="ellipsis"><?php echo $event['extra']['hit']['url'] ? $view['assets']->makeLinks($event['extra']['hit']['url']) : $view['translator']->trans('mautic.core.unknown'); ?></dd>
                    <?php if (isset($event['extra']['hit']['sourceName'])): ?>

                    <dt><?php echo $view['translator']->trans('mautic.core.source'); ?>:</dt>
                    <dd class="ellipsis">
                        <?php if (isset($event['extra']['hit']['sourceRoute'])): ?>
                        <a href="<?php echo $event['extra']['hit']['sourceRoute']; ?>" data-toggle="ajax">
                            <?php echo $event['extra']['hit']['sourceName']; ?>
                        </a>
                        <?php else: ?>
                        <?php echo $event['extra']['hit']['sourceName']; ?>
                        <?php endif; ?>
                    </dd>
                    <?php endif; ?>
                </dl>
                <div class="small">
                    <?php echo $event['extra']['hit']['userAgent']; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</li>
