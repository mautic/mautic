<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (isset($template)) {
    switch ($template) {
        case 'batchdelete':
            $confirmText = (!isset($confirmText)) ? $view['translator']->trans('mautic.core.form.delete')
                : $confirmText;
            $iconClass       = (!isset($iconClass)) ? 'fa fa-trash-o text-danger' : $iconClass;
            $btnText         = (!isset($btnText)) ? $view['translator']->trans('mautic.core.form.delete_selected') : $btnText;
            $btnClass        = (!isset($btnClass)) ? '' : $btnClass;
            $confirmCallback = (!isset($confirmCallback)) ? 'executeBatchAction' : $confirmCallback;
            $wrapOpeningTag  = (!isset($wrapOpeningTag)) ? '<li>' : $wrapOpeningTag;
            $wrapClosingTag  = (!isset($wrapClosingTag)) ? '</li>' : $wrapClosingTag;
            $precheck        = (!isset($precheck)) ? 'batchActionPrecheck' : $precheck;
            break;
        case 'delete':
            $confirmText = (!isset($confirmText)) ? $view['translator']->trans('mautic.core.form.delete')
                : $confirmText;
            $iconClass = (!isset($iconClass)) ? 'fa fa-fw fa-trash-o text-danger' : $iconClass;

            $btnText = (!isset($btnText)) ? $view['translator']->trans('mautic.core.form.delete') : $btnText;
            break;
    }
}

if (!isset($btnClass)) {
    $btnClass = 'btn btn-default';
}

if (!isset($btnTextAttr)) {
    $btnTextAttr = '';
}

if (isset($btnTextClass)) {
    $btnTextAttr .= ' class="'.$btnTextClass.'"';
}

if (!isset($confirmAction)) {
    $confirmAction = 'javascript:void(0);';
}

if (!isset($confirmCallback) && $confirmAction != 'javascript:void(0);') {
    $confirmCallback = 'executeAction';
}

if (!isset($cancelText)) {
    $cancelText = $view['translator']->trans('mautic.core.form.cancel');
}

if (!isset($cancelCallback)) {
    $cancelCallback = 'dismissConfirmation';
}

if (!isset($attr)) {
    $attr = [];
}

if (isset($confirmCallback)) {
    $attr[] = "data-confirm-callback=\"$confirmCallback\"";
}
if ($cancelText) {
    $attr[] = "data-cancel-text=\"{$view->escape($cancelText, 'js')}\"";
}
if ($cancelCallback) {
    $attr[] = "data-cancel-callback=\"$cancelCallback\"";
}
if (isset($target)) {
    $attr[] = "data-target=\"$target\"";
}

if (!isset($tag)) {
    $tag = 'a';
}

$buttonType = ($tag == 'button') ? ' type="button"' : '';

if (!isset($wrapOpeningTag)) {
    $wrapOpeningTag = $wrapClosingTag = '';
}

$tooltipAttr = '';
if (isset($tooltip)) {
    if (!isset($tooltipPosition)) {
        $tooltipPosition = 'left';
    }
    $tooltipAttr = ' data-toggle="tooltip" title="'.$tooltip.'" data-placement="'.$tooltipPosition.'"';
}

if (!isset($precheck)) {
    $precheck = '';
}

?>
<?php echo $wrapOpeningTag; ?>
<<?php echo $tag; ?><?php echo $buttonType; ?>
    class="<?php echo $btnClass; ?>"
    href="<?php echo $confirmAction; ?>"
    data-toggle="confirmation"
    data-precheck="<?php echo $precheck; ?>"
    data-message="<?php echo $view->escape($message); ?>"
    data-confirm-text="<?php echo $view->escape($confirmText); ?>" <?php echo implode(' ', $attr); ?>>
<span<?php echo $tooltipAttr; ?>>
    <?php if (isset($iconClass)): ?><i class="<?php echo $iconClass; ?>"></i>
    <?php endif; ?><?php if (isset($btnText)): ?><span<?php echo $btnTextAttr; ?>><?php echo $btnText; ?></span><?php endif; ?>
</span>
</<?php echo $tag; ?>><?php echo $wrapClosingTag; ?>
