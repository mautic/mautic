<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<ol class="traces logs">
    <?php foreach ($logs as $log) : ?>
        <?php $class = '';
        if ($log['priority'] >= 400) {
            $class = 'error';
        } elseif ($log['priority'] >= 300) {
            $class = 'warning';
        } ?>
        <li<?php echo $class ? ' class="' . $class . '"' : ''; ?>>
            <?php echo $log['priorityName'] . ' - ' . $log['message']; ?>
        </li>
    <?php endforeach; ?>
</ol>
