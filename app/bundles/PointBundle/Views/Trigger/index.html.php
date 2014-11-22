<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointTrigger');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.point.trigger.header.index'));
?>

<?php if ($permissions['point:triggers:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_pointtrigger_action', array("objectAction" => "new")); ?>"
        data-toggle="ajax"
        data-menu-link="#mautic_pointtrigger_index">
        <i class="fa fa-plus"></i>
        <?php echo $view["translator"]->trans("mautic.point.trigger.menu.new"); ?>
    </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_pointtrigger_index',
        'langVar'     => 'point.trigger',
        'routeBase'   => 'pointtrigger',
        'delete'      => $permissions['point:triggers:delete']
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
