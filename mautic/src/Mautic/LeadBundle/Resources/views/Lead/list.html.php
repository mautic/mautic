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
       onclick="Mautic.activateLead(<?php echo $item->getId(); ?>);"
       data-toggle="ajax"
       data-menu-link="mautic_lead_index">
        <div class="lead-profile<?php echo $activeClass; ?>" id="lead-<?php echo $item->getId(); ?>">
            <div class="padding-sm">
                <div class="pull-left">
                    <span class="lead-primary-identifier"><?php echo $view['translator']->trans($item->getPrimaryIdentifier($item)); ?></span>
                    <span class="lead-secondary-identifier"><?php echo $item->getSecondaryIdentifier($item); ?></span>
                </div>
                <div class="pull-right padding-sm">
                    <span class="badge"><?php echo $item->getScore(); ?></span>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </a>

    <div class="clearfix"></div>
<?php endforeach; ?>
<?php echo $view->render('MauticCoreBundle:Default:pagination.html.php', array(
    "items"           => $items,
    "page"            => $page,
    "limit"           => $limit,
    "menuLinkId"      => 'mautic_lead_index',
    "baseUrl"         => $view['router']->generate('mautic_lead_index'),
    "queryString"     => 'tmpl=list',
    "paginationClass" => "pagination-sm"
)); ?>
<?php else: ?>
<h4><?php echo $view['translator']->trans('mautic.core.noresults'); ?></h4>
<?php endif; ?>

<div class="lead-footer"></div>