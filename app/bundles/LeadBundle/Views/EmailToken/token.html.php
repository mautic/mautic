<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="page-list" id="form-email-tokens">
    <ul class="draggable scrollable">
        <?php foreach ($fields as $field): ?>
        <li class="page-list-item has-click-event">
            <div class="padding-sm">
                <span class="list-item-primary">
                    <?php echo $field['label']; ?>
                </span>
                <input type="hidden" class="email-token" value="{leadfield=<?php echo $field['alias']; ?>}" />
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>