<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($tmpl == 'index')
    $view->extend('MauticLeadBundle:Lead:index.html.php');
?>

<div class="table-responsive scrollable body-white padding-sm bundle-list">
    <?php if (count($items)): ?>
    <table class="table table-hover table-striped table-bordered role-list">
        <thead>
            <tr>
                <th class="col-lead-actions"></th>
                <?php
                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.dateAdded',
                    'text'       => 'mautic.lead.lead.thead.name',
                    'class'      => 'col-lead-name',
                    'default'    => true
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.dateAdded',
                    'text'       => 'mautic.lead.lead.thead.email',
                    'class'      => 'col-lead-email'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.dateAdded',
                    'text'       => 'mautic.lead.lead.thead.location',
                    'class'      => 'col-lead-location'
                ));

                echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', array(
                    'sessionVar' => 'lead',
                    'orderBy'    => 'l.score',
                    'text'       => 'mautic.lead.lead.thead.score',
                    'class'      => 'col-lead-score'
                ));
                ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php $fields = $model->organizeFieldsByAlias($item->getFields()); ?>
            <tr>
                <td>
                    <?php
                    echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                        'item'      => $item,
                        'edit'      => $security->hasEntityAccess(
                            $permissions['lead:leads:editown'],
                            $permissions['lead:leads:editother'],
                            $item->getOwner()
                        ),
                        'delete'    => $security->hasEntityAccess(
                            $permissions['lead:leads:deleteown'],
                            $permissions['lead:leads:deleteother'],
                            $item->getOwner()),
                        'routeBase' => 'lead',
                        'menuLink'  => 'mautic_lead_index',
                        'langVar'   => 'lead.lead'
                    ));
                    ?>
                </td>
                <td>
                    <a href="<?php echo $view['router']->generate('mautic_lead_action',
                        array("objectAction" => "view", "objectId" => $item->getId())); ?>"
                       data-toggle="ajax">
                        <div><?php echo $item->getPrimaryIdentifier(); ?></div>
                        <div class="small"><?php echo $item->getSecondaryIdentifier(); ?></div>
                    </a>
                </td>
                <td class="visible-md visible-lg"><?php echo $fields['email']; ?></td>
                <td class="visible-md visible-lg"><?php
                    if (!empty($fields['city']) && !empty($fields['state']))
                        echo $fields['city'] . ', ' . $fields['state'];
                    elseif (!empty($fields['city']))
                        echo $fields['city'];
                    elseif (!empty($fields['state']))
                        echo $fields['state'];
                    ?>
                </td>
                <td class="text-center">
                    <?php echo $item->getScore(); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
    <?php endif; ?>
    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "items"           => $items,
        "page"            => $page,
        "limit"           => $limit,
        "menuLinkId"      => 'mautic_lead_index',
        "baseUrl"         => $view['router']->generate('mautic_lead_index'),
        "tmpl"            => $indexMode,
        'sessionVar'      => 'lead'
    )); ?>
    <div class="footer-margin"></div>
</div>