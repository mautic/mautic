<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$data = $event['extra']['log']['metadata'];
if (isset($data['failed'])) {
    return;
}
?>

<dl class="dl-horizontal">
    <dt><?php echo $view['translator']->trans('mautic.notification.timeline.status'); ?></dt>
    <dd><?php echo $view['translator']->trans($data['status']); ?></dd>
    <dt><?php echo $view['translator']->trans('mautic.notification.timeline.type'); ?></dt>
    <dd><?php echo $view['translator']->trans($data['type']); ?></dd>
</dl>
<div class="small">
    <hr />
    <strong><?php echo $data['heading']; ?></strong>
    <br />
    <?php echo $data['content']; ?>
</div>
