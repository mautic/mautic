<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\LeadBundle\Security\Permissions\LeadPermissions;

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'leadlist');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.lead.list.header.index'));

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'templateButtons' => [
                'new' => $permissions[LeadPermissions::LISTS_CREATE],
            ],
            'routeBase' => 'segment',
            'langVar'   => 'lead.list',
            'tooltip'   => 'mautic.lead.lead.segment.add.help',
        ]
    )
);
?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:list_toolbar.html.php',
        [
            'searchValue' => $searchValue,
            'searchHelp'  => 'mautic.lead.list.help.searchcommands',
            'action'      => $currentRoute,
            'filters'     => (isset($filters)) ? $filters : [],
        ]
    ); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
