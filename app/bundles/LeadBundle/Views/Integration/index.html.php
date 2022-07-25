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
    <?php if ($count > 0 && 0 == $count % 2): echo '</div><div class="row">'; endif; ?>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?php if (isset($details['link'])): ?>
                        <a href="<?php echo $details['link']; ?>" class="pull-right"><i class="fa fa-external-link"></i></a>
                    <?php endif; ?>
                    <?php echo $details['integration']; ?>
                </h3>
            </div>
            <div class="panel-body">
                <dl class="dl-horizontal">
                    <dt><?php echo $view['translator']->trans('mautic.integration.object'); ?></dt>
                    <dd><?php echo $details['integration_entity']; ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.integration.object_id'); ?></dt>
                    <dd><?php echo $details['integration_entity_id']; ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.core.date.added'); ?></dt>
                    <dd><?php echo $view['date']->toText($details['date_added'], 'UTC', 'Y-m-d H:i:s', true); ?></dd>
                    <dt><?php echo $view['translator']->trans('mautic.integration.last_sync_date'); ?></dt>
                    <dd><?php echo $view['date']->toText($details['last_sync_date'], 'UTC', 'Y-m-d H:i:s', true); ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <?php ++$count; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
