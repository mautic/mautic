<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($item = ((isset($event['extra'])) ? $event['extra']['stat'] : false)):
    $type = isset($event['extra']['type']) ? $event['extra']['type'] : null;
    ?>
    <p>
        <?php if (!empty($item['isFailed']) && 'failed' == $type) :
            $details = json_decode($item['details'], true);
            $errors  = '';
            if (isset($details['failed'])) {
                $failedDetails = $details['failed'];
                if (!is_array($failedDetails)) {
                    $failedDetails = [$failedDetails];
                }
                $errors = implode('<br />', $failedDetails);
            }
            ?>
        <span class="text-danger mt-0 mb-10"><i class="fa fa-warning"></i>
            <?php
            if (!empty($errors)) {
                echo $errors;
            } else {
                echo $view['translator']->trans('mautic.sms.timeline.event.failed');
            }
        ?></span>

        <?php endif; ?>
        <?php if (!empty($item['list_name']) && 'failed' != $type) : ?>
            <br /><?php echo $view['translator']->trans('mautic.sms.timeline.event.list', ['%list%' => $item['list_name']]); ?>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if (isset($errors)): ?>
<p class="text-danger mt-0 mb-10">
    <i class="fa fa-warning"></i> <?php echo $view['translator']->trans('mautic.campaign.event.last_error').': '.$errors; ?>
</p>
<?php else: ?>
<dl class="dl-horizontal">
    <dt><?php echo $view['translator']->trans('mautic.sms.timeline.status'); ?></dt>
    <dd>
        <?php if (!empty($item['is_failed'])): ?>
            <?php echo $view['translator']->trans('mautic.email.stat.failed'); ?>
        <?php else: ?>
            <?php echo $view['translator']->trans('mautic.email.send'); ?>
        <?php endif; ?>
    </dd>
    <dt><?php echo $view['translator']->trans('mautic.sms.timeline.type'); ?></dt>
    <dd><?php echo $view['translator']->trans($item['type']); ?></dd>
</dl>
<div class="small">
    <hr />
    <strong><?php echo $view['translator']->trans('mautic.sms.timeline.content.heading'); ?></strong>
    <br />
    <?php echo $item['message']; ?>
</div>
<?php endif; ?>
