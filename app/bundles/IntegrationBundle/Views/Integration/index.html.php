<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'integration');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.integration.header.index'));
?>

<?php if ($permissions['integration:integrations:create']): ?>
    <?php $view['slots']->start('actions'); ?>
        <a id="reload-addons" href="<?php echo $this->container->get('router')->generate('mautic_integration_action', array('objectAction' => 'reload')); ?>" data-toggle="ajax" class="btn btn-default" data-menu-link="#mautic_integration_index">
            <i class="fa fa-plus"></i> <?php echo $view['translator']->trans('mautic.integration.reload.addons'); ?>
        </a>
    <?php $view['slots']->stop(); ?>

<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_integration_index',
        'langVar'     => 'integration',
        'routeBase'   => 'integration'
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
