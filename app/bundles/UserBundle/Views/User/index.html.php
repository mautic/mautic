<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'user');

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new' => $permissions['create']
    ),
    'routeBase' => 'user',
    'langVar'   => 'user.user'
)));
?>

<div class="panel panel-default bdr-t-wdh-0">
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array('searchValue' => $searchValue, 'action' => $currentRoute)); ?>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-files-o"></i></button>
                <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
	<?php $view['slots']->output('_content'); ?>
</div>
