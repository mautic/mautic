<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$wrap = true;
include 'action_button_helper.php';
?>
<div class="panel-body">
    <div class="box-layout">
        <div class="col-xs-6 va-m">
            <?php if (isset($searchValue)): ?>
            <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array(
                    'searchValue' => $searchValue,
                    'action'      => $action,
                    'searchHelp'  => (isset($searchHelp)) ? $searchHelp : ''
                )); ?>
            <?php endif; ?>
        </div>
        <div class="col-xs-6 va-m text-right">
            <?php //TODO - Support more buttons
            include 'action_button_helper.php';
            $buttonCount = 0;
            echo $view['buttons']->renderPreCustomButtons($buttonCount);

            if (!empty($templateButtons['delete'])):
                echo $view->render('MauticCoreBundle:Helper:confirm.html.php', array(
                    'message'       => $view['translator']->trans('mautic.' . $langVar . '.form.confirmbatchdelete'),
                    'confirmAction' => $view['router']->generate('mautic_' . $routeBase . '_action', array_merge(array('objectAction' => 'batchDelete'), $query)),
                    'template'      => 'batchdelete',
                    'tooltip'       => $view['translator']->trans('mautic.core.form.tooltip.bulkdelete'),
                    'precheck'      => 'batchActionPrecheck'
                ));
            endif;
            ?>
        </div>
    </div>
</div>
