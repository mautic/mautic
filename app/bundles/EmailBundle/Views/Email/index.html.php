<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'email');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.email.emails'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new'    => $permissions['email:emails:create']
    ),
    'routeBase' => 'email'
)));
?>

<div class="box-layout">
	<!-- filters -->
    <?php echo $view->render('MauticEmailBundle:Email:filters.html.php', array('filters' => $filters)); ?>
    <!--/ filters -->

    <div class="col-md-9 bg-auto height-auto bdr-l">
        <div class="panel panel-default bdr-t-wdh-0 bdr-l-wdh-0 mb-0">
            <?php echo $view->render('MauticCoreBundle:Helper:bulk_actions.html.php', array(
                'searchValue' => $searchValue,
                'searchHelp'  => 'mautic.email.help.searchcommands',
                'action'      => $currentRoute,
                'routeBase'   => 'email',
                'templateButtons' => array(
                    'delete' => $permissions['email:emails:deleteown'] || $permissions['email:emails:deleteother']
                )
            )); ?>

            <div class="page-list">
                <?php $view['slots']->output('_content'); ?>
            </div>
        </div>
    </div>
</div>

