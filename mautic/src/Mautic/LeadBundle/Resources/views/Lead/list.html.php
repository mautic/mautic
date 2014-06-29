<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php
if (count($items)):
foreach ($items as $key => $item):
    $activeClass = ($tmpl == 'index' && !empty($lead) && $item->getId() === $lead->getId()) ? " active" : "";
    ?>
    <a href="<?php echo $view['router']->generate('mautic_lead_action',
        array(
            'objectAction' => 'view',
            'objectId' => $item->getId(),
            'tmpl' => 'lead',

        )); ?>"
       onclick="Mautic.activateListItem('lead', <?php echo $item->getId(); ?>);"
       data-toggle="ajax"
       data-menu-link="mautic_lead_index">
        <div class="bundle-list-item<?php echo $activeClass; ?>" id="lead-<?php echo $item->getId(); ?>">
            <div class="padding-sm">
                <span class="list-item-primary"><?php echo $view['translator']->trans($item->getPrimaryIdentifier(true)); ?></span>
                <span class="list-item-secondary"><?php echo $item->getSecondaryIdentifier(); ?></span>
                <div class="badge-count padding-sm">
                    <span class="badge"><?php echo $item->getScore(); ?></span>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </a>

    <div class="clearfix"></div>
<?php endforeach; ?>
<?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
    "items"           => $items,
    "page"            => $page,
    "limit"           => $limit,
    "totalItems"      => $totalCount,
    "menuLinkId"      => 'mautic_lead_index',
    "baseUrl"         => $view['router']->generate('mautic_lead_index'),
    "queryString"     => 'tmpl=list',
    "paginationClass" => "sm",
    'tmpl'            => 'list',
    'target'          => '.leads',
    'sessionVar'      => 'lead'
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="footer-margin"></div>