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

if (isset($data['failed']) || !isset($data['timeline'])) {
    return;
}
?>

<dl class="dl-horizontal">
    <dt><?php echo $view['translator']->trans('mautic.dynamicContent.timeline.content'); ?></dt>
    <dd><?php echo $view['translator']->trans($data['timeline']); ?></dd>
</dl>
