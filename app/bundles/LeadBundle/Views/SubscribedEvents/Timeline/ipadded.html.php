<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$ip = $event['extra']['ipDetails'];

if (!is_object($ip)) {
    // Somehow the IP entity wasn't found

    return;
}

$details = $ip->getIpDetails();
$ipAddress = $ip->getIpAddress();

?>

<li class="wrapper">
    <div class="figure"><span class="fa fa-location-arrow"></span></div>
    <div class="panel">
        <div class="panel-body">
            <h3><?php echo $ipAddress; ?></h3>
            <p class="mb-0"><?php echo $view['translator']->trans('mautic.core.timeline.event.time', array('%date%' => $view['date']->toFullConcat($event['timestamp']), '%event%' => $event['eventLabel'])); ?></p>
        </div>

        <div class="panel-footer">
            <?php if (!empty($details['organization'])): ?>
                <i class="fa fa-building"> <?php echo $details['organization']; ?></i><br />
            <?php endif; ?>

            <?php
            $locations = array();
            if (!empty($details['city'])):
                $locations[] = $details['city'];
            endif;
            if (!empty($details['region'])):
                $locations[] = $details['region'];
            endif;
            if (!empty($details['country'])):
                $locations[] = $details['country'];
            endif;
            $location = implode(', ', $locations);
            if (!empty($location)): ?>
            <i class="fa fa-map-marker"></i> <?php echo $location; ?> </span>
            <?php endif; ?>
        </div>
    </div>
</li>
