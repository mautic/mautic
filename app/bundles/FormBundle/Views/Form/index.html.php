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
$view['slots']->set('mauticContent', 'form');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.form.forms'));

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'templateButtons' => [
                'new' => $permissions['form:forms:create'],
            ],
            'routeBase' => 'form',
            'langVar'   => 'form.form',
        ]
    )
);

?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render(
        'MauticCoreBundle:Helper:list_toolbar.html.php',
        [
            'searchValue'   => $searchValue,
            'searchHelp'    => 'mautic.form.form.help.searchcommands',
            'searchId'      => 'form-search',
            'action'        => $currentRoute,
            'customButtons' => [
                [
                    'confirm' => [
                        'message'       => $view['translator']->trans('mautic.form.confirm_batch_rebuild'),
                        'confirmText'   => $view['translator']->trans('mautic.form.rebuild'),
                        'confirmAction' => $view['router']->path(
                            'mautic_form_action',
                            array_merge(['objectAction' => 'batchRebuildHtml'])
                        ),
                        'tooltip'         => $view['translator']->trans('mautic.form.rebuild.batch_tooltip'),
                        'iconClass'       => 'fa fa-fw fa-refresh',
                        'btnText'         => false,
                        'btnClass'        => 'btn btn-sm btn-default',
                        'precheck'        => 'batchActionPrecheck',
                        'confirmCallback' => 'executeBatchAction',
                    ],
                    'primary' => true,
                ],
            ],
        ]
    ); ?>

    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>

