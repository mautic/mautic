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

<div class="media pt-sm pb-sm pr-md pl-md nm bdr-b notification" id="notification<?php echo $n['id']; ?>">
    <span class="pull-left mt-xs" style="width:36px">
        <?php if (strpos($n['iconClass'], 'img:') === 0): ?>
        <span class="img-wrapper img-rounded">
            <img class="media-object" src="<?php echo substr($n['iconClass'], 4); ?>" />
        </span>
        <?php else: ?>
        <?php $tooltip = (!empty($n['type'])) ? ' data-toggle="tooltip" title="'.$view['translator']->trans('mautic.notifications.type.'.$n['type']).'"' : ''; ?>
        <i class="fa fa-2x <?php echo (!empty($n['iconClass'])) ? $n['iconClass'] : 'fa-star'; ?>"<?php echo $tooltip; ?>></i>
        <?php endif; ?>
    </span>
    <a href="javascript:void(0);" class="btn btn-default btn-xs btn-nospin pull-right do-not-close" data-toggle="tooltip" title="<?php echo $view['translator']->trans('mautic.core.notifications.clear'); ?>" onclick="Mautic.clearNotification(<?php echo $n['id']; ?>);"><i class="fa fa-times do-not-close"></i></a>
    <?php if (!$n['isRead']): ?>
        <span class="pull-right is-unread text-danger"><i class="fa fa-asterisk"></i></span>
    <?php endif; ?>
    <div class="media-body">
        <?php if (!empty($n['header'])): ?>
            <div class="media-heading fw-sb mb-0 text-primary"><?php echo $view['formatter']->_($n['header']); ?></div>
        <?php endif; ?>
        <div><?php echo $view['formatter']->_($n['message'], 'html'); ?></div>
        <div class="clearfix mt-xs">
            <span class="fa fa-clock-o text-success pull-left mr-xs"></span>
            <span class="fs-10 text-muted pull-left"><?php echo $view['date']->toText($n['dateAdded']); ?></span>
        </div>
    </div>
</div>