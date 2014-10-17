<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$item = $event['extra']['stats'];

?>

<span class="icon fa fa-send"></span>
<a href="<?php echo $view['router']->generate('mautic_email_action',
    array("objectAction" => "view", "objectId" => $item['email_id'])); ?>"
   data-toggle="ajax">
    <?php echo $item['subject']; ?>
</a>
<p><?php echo $item['plainText']; ?></p>