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

<div class="table-responsive body-white padding-sm leadlist-list">
    <?php if (count($items)): ?>
    <table class="table table-hover table-striped table-bordered">
        <thead>
        <tr>
            <th><?php echo $view['translator']->trans('mautic.lead.list.thead.name'); ?></th>
            <th class="visible-md visible-lg"><?php echo $view['translator']->trans('mautic.lead.list.thead.descr'); ?></th>
            <th class="visible-md visible-lg"><?php echo $view['translator']->trans('mautic.lead.list.thead.id'); ?></th>
            <th style="width: 75px;"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):?>
            <tr>
                <td>
                    <a href="<?php echo $view['router']->generate('mautic_lead_index', array('search' => "$listCommand:{$item->getAlias()}")); ?>"
                       data-toggle="ajax">
                        <?php echo $item->getName(); ?> (<?php echo $item->getAlias(); ?>)
                    </a>
                </td>
                <td class="visible-md visible-lg"><?php echo $item->getDescription(); ?></td>
                <td class="visible-md visible-lg"><?php echo $item->getId(); ?></td>
                <td>
                    <span class="lead-actions">
                        <?php
                        echo $view->render('MauticCoreBundle:Default:actions.html.php', array(
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
                        ));
                        ?>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo $view->render('MauticCoreBundle:Default:pagination.html.php', array(
        "items"   => $items,
        "page"    => $page,
        "limit"   => $limit,
        "baseUrl" =>  $view['router']->generate('mautic_leadlist_index')
    )); ?>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
</div>