<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$wrap = true;
$view['buttons']->reset(\Mautic\CoreBundle\Templating\Helper\ButtonHelper::LOCATION_TOOLBAR_ACTIONS);
include 'action_button_helper.php';
?>
<div class="panel-body">
    <div class="box-layout">
        <div class="col-xs-6 col-lg-8 va-m form-inline">
            <?php if (isset($searchValue)): ?>
            <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', [
                    'searchId'    => (empty($searchId)) ? null : $searchId,
                    'searchValue' => $searchValue,
                    'action'      => $action,
                    'searchHelp'  => (isset($searchHelp)) ? $searchHelp : '',
                    'target'      => (empty($target)) ? null : $target,
                    'tmpl'        => (empty($tmpl)) ? null : $tmpl,
                ]); ?>
            <?php endif; ?>

            <?php if (!empty($filters)): ?>
            <?php echo $view->render('MauticCoreBundle:Helper:list_filters.html.php', [
                    'filters' => $filters,
                    'target'  => (empty($target)) ? null : $target,
                    'tmpl'    => (empty($tmpl)) ? null : $tmpl,
                ]); ?>
            <?php endif; ?>
        </div>

        <div class="col-xs-6 col-lg-4 va-m text-right">
            <?php
            echo $view['buttons']->renderPreCustomButtons($buttonCount);

            if (!empty($templateButtons['delete'])):
                echo $view->render('MauticCoreBundle:Helper:confirm.html.php', [
                    'message'       => $view['translator']->trans('mautic.'.$langVar.'.form.confirmbatchdelete'),
                    'confirmAction' => $view['router']->path($actionRoute, array_merge(['objectAction' => 'batchDelete'], $query)),
                    'template'      => 'batchdelete',
                    'tooltip'       => $view['translator']->trans('mautic.core.form.tooltip.bulkdelete'),
                    'precheck'      => 'batchActionPrecheck',
                    'target'        => (empty($target)) ? null : $target,
                ]);
                ++$buttonCount;
            endif;

            echo $view['buttons']->renderPostCustomButtons($buttonCount);
            ?>
        </div>
    </div>
</div>
