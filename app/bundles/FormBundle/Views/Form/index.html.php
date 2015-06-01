<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'form');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.form.forms'));

$view['slots']->set('actions', $view->render('MauticCoreBundle:Helper:page_actions.html.php', array(
    'templateButtons' => array(
        'new'    => $permissions['form:forms:create']
    ),
    'routeBase' => 'form',
    'langVar'   => 'form.form'
)));

$tabs = array('standalone', 'campaign');
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <!-- tabs controls -->
    <ul class="nav nav-tabs pr-md pl-md bg-auto" data-toggle="tab-hash">
        <?php foreach ($tabs as $k => $tab): ?>
            <li<?php if ($k === 0) echo ' class="active"'; ?>>
                <a href="#tab-<?php echo $tab; ?>" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.form.type.' . $tab); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <!--/ tabs controls -->
    <div class="tab-content">
        <?php foreach ($tabs as $k => $tab): ?>
            <div class="tab-pane fade bdr-w-0<?php if ($k === 0) echo ' in active'; ?>" id="tab-<?php echo $tab; ?>">
                <div class="pl-0 pr-0 bg-auto bdr-l">
                    <div class="panel panel-default bdr-t-wdh-0 bdr-l-wdh-0 mb-0">
                        <?php echo $view->render('MauticCoreBundle:Helper:list_toolbar.html.php', array(
                            'searchValue' => ${$tab}['searchValue'],
                            'searchHelp'  => 'mautic.form.form.help.searchcommands',
                            'searchId'    => $tab . '-search',
                            'action'      => $currentRoute,
                            'routeBase'   => 'form',
                            'target'      => '.' . $tab . '-container',
                            'tmpl'        => $tab,
                            'templateButtons' => array(
                                'delete' => $permissions['form:forms:deleteown'] || $permissions['form:forms:deleteother']
                            ),
                            'preCustomButtons' => array(
                                array(
                                    'confirm'      => array(
                                        'message'         => $view['translator']->trans('mautic.form.confirm_batch_rebuild'),
                                        'confirmText'     => $view['translator']->trans("mautic.form.rebuild"),
                                        'confirmAction'   => $view['router']->generate('mautic_form_action', array_merge(array('objectAction' => 'batchRebuildHtml'))),
                                        'tooltip'         => $view['translator']->trans('mautic.form.rebuild.batch_tooltip'),
                                        'iconClass'       => 'fa fa-fw fa-refresh',
                                        'btnText'         => false,
                                        'btnClass'        => 'btn btn-sm btn-default',
                                        'precheck'        => 'batchActionPrecheck',
                                        'confirmCallback' => 'executeBatchAction'
                                    )
                                )
                            ),
                        )); ?>

                        <div class="<?php echo $tab; ?>-container">
                            <?php echo $view->render('MauticFormBundle:Form:list.html.php', array(
                                'items'       => ${$tab}['items'],
                                'totalItems'  => ${$tab}['totalItems'],
                                'page'        => ${$tab}['page'],
                                'limit'       => ${$tab}['limit'],
                                'tmpl'        => ${$tab}['tmpl'],
                                'permissions' => $permissions,
                                'security'    => $security
                            ));?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

