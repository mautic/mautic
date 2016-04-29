<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


$data = $event['extra']['log']['metadata'];

if (isset($data['failed'])) {
    return;
}
?>

<li class="wrapper form-submitted">
    <div class="figure"><span class="fa <?php echo isset($icons['notification']) ? $icons['notification'] : '' ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3><?php echo $data['name']; ?></h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>
        <div class="panel-footer">
            <dl class="dl-horizontal">
                <dt><?php echo $view['translator']->trans('mautic.notification.timeline.status'); ?></dt>
                <dd class="ellipsis"><?php echo $view['translator']->trans($data['status']); ?></dd>
                <dt><?php echo $view['translator']->trans('mautic.notification.timeline.type'); ?></dt>
                <dd class="ellipsis"><?php echo $view['translator']->trans($data['type']); ?></dd>
            </dl>
            <div class="small">
                <hr />
                <strong><?php echo $data['heading']; ?></strong>
                <br />
                <?php echo $data['content']; ?>
            </div>
        </div>
    </div>
</li>

