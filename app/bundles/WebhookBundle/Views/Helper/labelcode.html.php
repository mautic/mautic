<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($code == '200'): ?>
    <span class="label label-success">
         <?php echo $code; ?>
    </span>
    <?php echo $view['translator']->trans('mautic.webhook.label.success'); ?>
<?php else: ?>
    <span class="label label-warning">
         <?php echo $code; ?>
    </span>
    <?php echo $view['translator']->trans('mautic.webhook.label.warning'); ?>
<?php endif; ?>
