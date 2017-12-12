<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<p class="alert alert-info">
    <strong><?php echo $view['translator']->trans('mautic.plugin.messenger.callback.url'); ?></strong>
    <br>
    <?php
    echo str_replace('http://','https://', $view['router']->url('messenger_callback'));
    ?>
</p>
