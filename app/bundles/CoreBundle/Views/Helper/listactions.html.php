<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<div class="panel-body">
    <div class="box-layout">
        <div class="col-xs-6 va-m">
            <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array('searchValue' => $searchValue, 'action' => $action)); ?>
        </div>
        <div class="col-xs-6 va-m text-right">
            <?php //TODO - Support more buttons ?>
            <?php if ($delete) : ?>
            <button type="button" class="btn btn-sm btn-danger"
               onclick="Mautic.showConfirmation(
                   '<?php echo $view->escape($view['translator']->trans('mautic.' . $langVar . '.form.confirmbatchdelete'), 'js'); ?>',
                   '<?php echo $view->escape($view['translator']->trans('mautic.core.form.delete'), 'js'); ?>',
                   'executeBatchAction',
                   ['<?php echo $view['router']->generate('mautic_' . $routeBase . '_action', array('objectAction' => 'batchDelete')); ?>',
                   '#<?php echo $menuLink; ?>'],
                   '<?php echo $view->escape($view['translator']->trans('mautic.core.form.cancel'), 'js'); ?>','',[]);">
                <i class="fa fa-trash-o"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
