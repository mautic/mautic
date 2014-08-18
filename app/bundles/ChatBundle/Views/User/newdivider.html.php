<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$tag = (empty($tag)) ? 'li' : $tag;
?>
<<?php echo $tag; ?> class="chat-new-divider">
  <span><?php echo $view['translator']->trans('mautic.chat.chat.new.messages'); ?></span>
</<?php echo $tag; ?>>