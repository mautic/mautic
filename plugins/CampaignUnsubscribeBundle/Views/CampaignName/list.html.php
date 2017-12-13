<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('CampaignUnsubscribeBundle:CampaignName:fields.html.php');
}
?>

<?php if (count($items)): ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered campaign-name-list" id="fieldsTable">
            <thead>
            <tr>
                <?php
                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'checkall' => 'true',
                        'target' => '#fieldsTable',
                        'routeBase' => 'unsubscribe_campaign_names',
                        'templateButtons' => [
                            //'delete' => $permissions[$permissionBase . ':delete'],
                            'delete' => true,
                        ],
                        'query' => [
                            'bundle' => $bundle,
                        ],
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'unsubscribeCampaignName',
                        'orderBy' => 'ucn.name',
                        'text' => 'mautic.campaign.campaign',
                        'class' => 'visible-md visible-lg',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'unsubscribeCampaignName',
                        'orderBy' => 'ucn.campaign',
                        'text' => 'mautic.core.label',
                    ]
                );

                echo $view->render(
                    'MauticCoreBundle:Helper:tableheader.html.php',
                    [
                        'sessionVar' => 'unsubscribeCampaignName',
                        'orderBy' => 'ucn.id',
                        'text' => 'mautic.core.id',
                        'class' => 'visible-md visible-lg col-page-id',
                        'default' => true,
                    ]
                );
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render(
                            'MauticCoreBundle:Helper:list_actions.html.php',
                            [
                                'item' => $item,
                                'templateButtons' => [
                                    'delete' => true,
                                    'edit' => false
                                ],
                                'editMode' => 'ajaxmodal',
                                'editAttr' => [
                                    'data-target' => '#MauticSharedModal',
                                    'data-header' => 'testing',
                                    'data-toggle' => 'ajaxmodal',
                                ],
                                'translationBase' => 'plugin.livechat_survey_fields',
                                'routeBase' => 'unsubscribe_campaign_names',
                            ]
                        );
                        ?>
                    </td>
                    <td>
                        <?php echo $item->getCampaign()->getName(); ?>
                    </td>
                    <td>
                        <?php echo $item->getName(); ?>
                    </td>
                    <td>
                        <?php echo $item->getId(); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="panel-footer">
            <?php /*echo $view->render(
                        'MauticCoreBundle:Helper:pagination.html.php',
                        [
                            'totalItems' => count($items),
                            'page' => $page,
                            'limit' => $limit,
                            'menuLinkId' => 'plugin_livechat_manage_survey_fields',
                            'baseUrl' => $view['router']->path(
                                'plugin_livechat_manage_survey_fields'
                            ),
                            'sessionVar' => 'surveyField',
                        ]
                    );*/ ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Helper:noresults.html.php'); ?>
<?php endif; ?>