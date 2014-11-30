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

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'customButtons' => array(
        array(
            'attr' => array(
                'data-toggle' => 'ajax',
                'href'        => $view['router']->generate('mautic_integration_action', array('objectAction' => 'reload'))
            ),
            'btnText' => $view["translator"]->trans('mautic.integration.reload.addons'),
            'iconClass' => 'fa fa-plus'
        )
    )
)));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:bulk_actions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'routeBase'   => 'integration',
        'templateButtons' => array()
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
