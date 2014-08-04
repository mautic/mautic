<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//use a placeholder to use for the prototype
$text = (empty($message)) ? '[message]' : $message['message'];
?>
<p class="media-text"><?php echo $text; ?></p>
<span class="clearfix"></span>