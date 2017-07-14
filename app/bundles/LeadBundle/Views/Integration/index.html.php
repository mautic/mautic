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
        <div class="panel panel-default">
            <div class="panel-heading pr-0">
                <h3 class="panel-title"><?php echo $details['integration']; ?></h3>
            </div>
            <div class="panel-collapse pull out">
                <dl class="dl-horizontal">
                    <dt>Object</dt>
                    <dd><?php echo $details['integration_entity']; ?></dd>
                    <dt>Object ID</dt>
                    <dd><?php echo $details['integration_entity_id']; ?></dd>
                    <dt>Date Added</dt>
                    <dd><?php echo $view['date']->toText($details['date_added'], 'local', 'Y-m-d H:i:s', true); ?></dd>
                    <dt>Last Sync Date</dt>
                    <dd><?php echo $view['date']->toText($details['last_sync_date'], 'local', 'Y-m-d H:i:s', true); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <?php ++$count; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
