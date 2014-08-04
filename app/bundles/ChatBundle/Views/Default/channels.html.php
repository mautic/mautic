<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<ul class="topmenu" id="ChatChannels">
    <?php foreach ($channels as $channel): ?>
    <li>
        <a href="javascript:void(0);">
            <span class="text"># <?php echo $channel['name']; ?></span>
        </a>
    </li>
<?php endforeach; ?>
</ul>