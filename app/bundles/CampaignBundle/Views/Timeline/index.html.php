<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['log'];

?>

<span class="icon fa fa-clock-o"></span>
Triggered
<strong><?php echo $item['eventName']; ?></strong> event from 
<a href="<?php echo $view['router']->generate('mautic_campaign_action',
    array("objectAction" => "view", "objectId" => $item['campaign_id'])); ?>"
   data-toggle="ajax">
    <?php echo $item['campaignName']; ?>
</a>