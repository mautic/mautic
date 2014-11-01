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
<?php if (count($items)): ?>
<div class="panel panel-default">
    <div class="panel-body">
        <div class="box-layout">
            <div class="col-xs-6 va-m">
                <div class="checkbox-inline custom-primary">
                    <label class="mb-0">
                        <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#campaignTable">
                        <span></span>
                        <?php echo $view['translator']->trans('mautic.core.table.selectall'); ?>
                    </label>
                </div>
            </div>
            <div class="col-xs-6 va-m text-right">
                <button type="button" class="btn btn-sm btn-warning"><i class="fa fa-files-o"></i></button>
                <button type="button" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-striped table-bordered campaign-list" id="campaignTable">
            <thead>
            <tr>
                <th class="visible-md visible-lg col-campaign-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 'c.name',
                    'text'       => 'mautic.campaign.thead.name',
                    'class'      => 'col-campaign-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 'c.description',
                    'text'       => 'mautic.campaign.thead.description',
                    'class'      => 'visible-md visible-lg col-campaign-description'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'campaign',
                    'orderBy'    => 'c.id',
                    'text'       => 'mautic.campaign.thead.id',
                    'class'      => 'visible-md visible-lg col-campaign-id'
                ));
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="visible-md visible-lg">
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
    </div>
    <div class="panel-footer">
        <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
            "totalItems"      => count($items),
            "page"            => $page,
            "limit"           => $limit,
            "menuLinkId"      => 'mautic_campaign_index',
            "baseUrl"         => $view['router']->generate('mautic_campaign_index'),
            'sessionVar'      => 'campaign'
        )); ?>
    </div>
</div>
 <?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>
