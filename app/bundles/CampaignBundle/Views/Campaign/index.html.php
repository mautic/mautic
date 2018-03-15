<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.campaign.campaigns'));

$defaultCampaign = $view->container->getParameter('mautic.campaign_default_for_template');
$forceDefault    = $view->container->getParameter('mautic.campaign_force_default');
$new             = $permissions['campaign:campaigns:create'];
$customButtons   = !isset($customButtons) ? [] : $customButtons;
if ($permissions['campaign:campaigns:create'] && !empty($defaultCampaign)) {
    $btnText   = $forceDefault ? $view['translator']->trans('mautic.core.form.new') : $view['translator']->trans(
        'mautic.campaign.button.new_from_template'
    );
    $iconClass = $forceDefault ? 'fa fa-plus' : 'fa fa-copy';

    $customButtons[] = [
        'attr'      => [
            'href' => $view['router']->path(
                'mautic_campaign_action',
                ['objectAction' => 'clone', 'objectId' => $defaultCampaign]
            ),
        ],
        'iconClass' => $iconClass,
        'btnText'   => $btnText,
    ];

    // hide "new" template button, replace with custom button if we should force default
    $new = $forceDefault ? false : true;
}

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'templateButtons' => [
                'new' => $new,
            ],
            'customButtons'   => $customButtons,

            'routeBase' => 'campaign',
        ]
    )
);
?>

<div class="panel panel-default bdr-t-wdh-0">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:list_toolbar.html.php',
        [
            'searchValue' => $searchValue,
            'searchHelp'  => 'mautic.core.help.searchcommands',
            'action'      => $currentRoute,
            'filters'     => $filters,
        ]
    ); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>