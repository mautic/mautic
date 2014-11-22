<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', $application);
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.mapper.clients.title'));
?>

<?php if ($permissions[$application.':mapper:create']): ?>
    <?php $view['slots']->start("actions"); ?>
    <a class="btn btn-default" href="<?php echo $this->container->get('router')->generate(
        'mautic_mapper_client_action', array(
        "objectAction" => "new",
        "application"       => $application
    )); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_category_index">
        <i class="fa fa-plus"></i>
        <?php echo $view["translator"]->trans("mautic.mapper.menu.new.client"); ?>
    </a>
    <?php $view['slots']->stop(); ?>
<?php endif; ?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php //TODO - Restore these buttons to the listactions when custom content is supported
    /*<div class="btn-group">
        <button type="button" class="btn btn-default"><i class="fa fa-upload"></i></button>
        <button type="button" class="btn btn-default"><i class="fa fa-archive"></i></button>
    </div>*/ ?>
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_mapper_client_index',
        'langVar'     => 'mapper',
        'routeBase'   => 'mapper_client',
        'delete'      => $permissions[$application . ':mapper:delete'],
        'extra'       => array(
            'application' => $application
        )
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
