<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['log'];

?>

<li class="wrapper moved-stage">
    <div class="figure"><span class="fa <?php echo isset($event['icon']) ? $event['icon'] : '' ?>"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3>
                <span class="text-primary"><?php echo $item['eventName']; ?>:</span> <?php echo $item['actionName']; ?>
            </h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>
        <div class="panel-footer">
            <p><?php
                $stageName = explode(":",$item['eventName']);
                echo $view['translator']->trans('mautic.stage.timeline.event.stage.change', array('%name%' => $stageName[1])); ?></p>
        </div>
    </div>
</li>
