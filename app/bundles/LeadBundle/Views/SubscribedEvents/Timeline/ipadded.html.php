<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$ip = $event['extra']['ipDetails'];

if (!is_object($ip)) {
    // Somehow the IP entity wasn't found

    return;
}

$details = $ip->getIpDetails();
?>

<?php if (!empty($details['organization'])): ?>
    <i class="fa fa-building"> <?php echo $details['organization']; ?></i><br />
<?php endif; ?>

<?php
$locations = [];
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
