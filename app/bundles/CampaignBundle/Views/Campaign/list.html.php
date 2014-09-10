<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
$view->extend('MauticCampaignBundle:Campaign:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm page-list">
    <?php if (count($items)): ?>
        <table class="table table-hover table-striped table-bordered campaign-list">
            <thead>
            <tr>
                <th class="col-campaign-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 't.name',
                    'text'       => 'mautic.campaign.thead.name',
                    'class'      => 'col-campaign-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 't.description',
                    'text'       => 'mautic.campaign.thead.description',
                    'class'      => 'col-campaign-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 't.id',
                    'text'       => 'mautic.campaign.thead.id',
                    'class'      => 'col-campaign-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $permissions['campaign:campaigns:edit'],
                            'clone'     => $permissions['campaign:campaigns:create'],
                            'delete'    => $permissions['campaign:campaigns:delete'],
                            'routeBase' => 'campaign',
                            'menuLink'  => 'mautic_campaign_index',
                            'langVar'   => 'campaign'
                        ));
                        ?>
                    </td>
                    <td>
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'       => $item,
                            'model'      => 'campaign'
                        )); ?>
                        <a href="<?php echo $view['router']->generate('mautic_campaign_action',
                            array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getName(); ?>
                        </a>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "totalItems"      => count($items),
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_campaign_index',
        "baseUrl"         => $view['router']->generate('mautic_campaign_index'),
        'sessionVar'      => 'campaign'
    )); ?>
    <div class="footer-margin"></div>
</div>
