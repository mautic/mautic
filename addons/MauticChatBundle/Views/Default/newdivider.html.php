<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$tag = (empty($tag)) ? 'li' : $tag;
?>
<<?php echo $tag; ?> class="chat-new-divider text-center text-danger pt-md pb-md">
  <span><?php echo $view['translator']->trans('mautic.chat.chat.new.messages'); ?></span>
</<?php echo $tag; ?>>