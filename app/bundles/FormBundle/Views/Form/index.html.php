<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.form.form.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_form_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.form.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.form.form.help.searchcommands'));
?>

<?php if ($permissions['form:forms:create']): ?>
    <?php $view['slots']->start("actions"); ?>
        <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate('mautic_form_action', array("objectAction" => "new")); ?>" data-toggle="ajax" data-menu-link="#mautic_form_index">
            <i class="fa fa-plus"></i>
            <?php echo $view["translator"]->trans("mautic.form.form.menu.new"); ?>
		</a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array('searchValue' => $searchValue, 'action' => $currentRoute)); ?>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-sm btn-danger"
                   onclick="Mautic.showConfirmation(
                       '<?php echo $view->escape($view['translator']->trans('mautic.form.form.confirmbatchdelete'), 'js'); ?>',
                       '<?php echo $view->escape($view['translator']->trans('mautic.core.form.delete'), 'js'); ?>',
                       'executeBatchAction',
                       ['<?php echo $view['router']->generate('mautic_form_action', array('objectAction' => 'batchDelete')); ?>',
                       '#mautic_form_index'],
                       '<?php echo $view->escape($view['translator']->trans('mautic.core.form.cancel'), 'js'); ?>','',[]);">
                    <i class="fa fa-trash-o"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
