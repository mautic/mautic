<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
//Check to see if the entire page should be displayed or just main content
if ($tmpl == 'index'):
    $view->extend('MauticLeadBundle:List:index.html.php');
endif;
$listCommand = $view['translator']->trans('mautic.lead.lead.searchcommand.list');
?>

<?php if (count($items)): ?>
    <div class="table-responsive page-list">
        <table class="table table-hover table-striped table-bordered" id="leadListTable">
            <thead>
            <tr>
                <th class="col-leadlist-actions pl-20">
                    <div class="checkbox-inline custom-primary">
                        <label class="mb-0 pl-10">
                            <input type="checkbox" id="customcheckbox-one0" value="1" data-toggle="checkall" data-target="#leadListTable">
                            <span></span>
                        </label>
                    </div>
                </th>
                <th class="col-leadlist-name"><?php echo $view['translator']->trans('mautic.lead.list.thead.name'); ?></th>
                <th class="visible-md visible-lg col-leadlist-descr"><?php echo $view['translator']->trans('mautic.lead.list.thead.descr'); ?></th>
                <th class="visible-md visible-lg col-leadlist-leadcount"><?php echo $view['translator']->trans('mautic.lead.list.thead.leadcount'); ?></th>
                <th class="visible-md visible-lg col-leadlist-id"><?php echo $view['translator']->trans('mautic.lead.list.thead.id'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):?>
                <tr>
                    <td>
                        <?php
                        echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                            'item'      => $item,
                            'edit'      => $security->hasEntityAccess(
                                true,
                                $permissions['lead:lists:editother'],
                                $item->getCreatedBy()
                            ),
                            'delete'    => $security->hasEntityAccess(
                                true,
                                $permissions['lead:lists:deleteother'],
                                $item->getCreatedBy()
                            ),
                            'routeBase' => 'leadlist',
                            'menuLink'  => 'mautic_leadlist_index',
                            'langVar'   => 'lead.list'
                        ))  ;
                        ?>
                    </td>
                    <td>
                        <?php if ($item->isGlobal()): ?>
                        <i class="fa fa-fw fa-globe"></i>
                        <?php endif; ?>
                        <a href="<?php echo $view['router']->generate('mautic_lead_index', array('search' => "$listCommand:{$item->getAlias()}")); ?>"
                           data-toggle="ajax">
                            <?php echo $item->getName(); ?> (<?php echo $item->getAlias(); ?>)
                        </a>
                        <?php if (!$item->isGlobal() && $currentUser->getId() != $item->getCreatedBy()->getId()): ?>
                        <br />
                        <span class="small">(<?php echo $item->getCreatedBy()->getName(); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
                    <td class="visible-md visible-lg"><?php echo count($item->getIncludedLeads()); ?></td>
                    <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="panel-footer">
            <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
                "totalItems" => count($items),
                "page"       => $page,
                "limit"      => $limit,
                "baseUrl"    =>  $view['router']->generate('mautic_leadlist_index'),
                'sessionVar' => 'leadlist'
            )); ?>
        </div>
    </div>
<?php else: ?>
    <?php echo $view->render('MauticCoreBundle:Default:noresults.html.php'); ?>
<?php endif; ?>
