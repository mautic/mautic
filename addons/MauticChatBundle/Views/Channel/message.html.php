<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//use a placeholder to use for the prototype
$text = (empty($message)) ? '[message]' : $message['message'];
?>
<p class="media-text" id="ChatMessage<?php echo $message['id']; ?>"><?php echo $text; ?></p>
<span class="clearfix"></span>