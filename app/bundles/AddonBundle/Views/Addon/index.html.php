<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'addon');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.addon.manage.addons'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'customButtons' => array(
        array(
            'attr'      => array(
                'data-toggle' => 'ajax',
                'href'        => $view['router']->generate('mautic_addon_action', array('objectAction' => 'reload'))
            ),
            'btnText'   => $view["translator"]->trans('mautic.addon.reload.addons'),
            'iconClass' => 'fa fa-cubes',
            'tooltip'   => 'mautic.addon.reload.addons.tooltip'
        )
    )
)));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
        'searchValue' => $searchValue,
        'searchHelp'  => 'mautic.addon.help.searchcommands',
        'action'      => $currentRoute,
        'routeBase'   => 'addon',
        'templateButtons' => array()
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
