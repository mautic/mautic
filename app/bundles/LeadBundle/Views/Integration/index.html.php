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
<?php if (empty($integrations)): ?>
<div class="alert alert-warning col-md-6 col-md-offset-3 mt-md">
    <h4><?php echo $view['translator']->trans('mautic.lead.integrations.header'); ?></h4>
</div>
<?php else: ?>
<?php $count = 0; ?>
<div class="row">
<?php foreach ($integrations as $details): ?>
    <?php if ($count > 0 && $count % 2 == 0): echo '</div><div class="row">'; endif; ?>
    <div class="col-md-6">
        <?php dump($details); ?>
    </div>
    <?php ++$count; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
