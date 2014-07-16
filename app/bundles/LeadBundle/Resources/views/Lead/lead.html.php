<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticLeadBundle:Lead:index.html.php');
}
?>

<?php if (!empty($lead)): ?>
<div class="bundle-main-header">
    <div class="pull-left">
        <span class="bundle-main-item-primary">
            <span class="bundle-main-actions">
                <?php
                echo $view->render('MauticCoreBundle:Helper:actions.html.php', array(
                    'item'      => $lead,
                    'edit'      => $security->hasEntityAccess(
                        $permissions['lead:leads:editown'],
                        $permissions['lead:leads:editother'],
                        $lead->getOwner()
                    ),
                    'delete'    => $security->hasEntityAccess(
                        $permissions['lead:leads:deleteown'],
                        $permissions['lead:leads:deleteother'],
                        $lead->getOwner()),
                    'routeBase' => 'lead',
                    'menuLink'  => 'mautic_lead_index',
                    'langVar'   => 'lead.lead'
                ));
                ?>
            </span>
            <?php echo $view['translator']->trans($lead->getPrimaryIdentifier()); ?>
        </span>
        <span class="bundle-main-item-secondary"><?php echo $lead->getSecondaryIdentifier(); ?></span>
    </div>
    <div class="badge-count pull-right">
        <h1><span class="label label-success"><?php echo $lead->getScore(); ?></span></h1>
    </div>
    <div class="clearfix"></div>
</div>

<?php
    echo $view->render('MauticLeadBundle:Lead:info.html.php', array("lead" => $lead, 'dateFormats' => $dateFormats));

    $scoreLog = count($lead->getScoreChangeLog());
    if (!empty($scoreLog))
        echo $view->render('MauticLeadBundle:Lead:score_log.html.php', array("lead" => $lead, 'dateFormats' => $dateFormats));

    $ipAddresses = count($lead->getIpAddresses());
    if (!empty($ipAddresses))
        echo $view->render('MauticLeadBundle:Lead:ip_addresses.html.php', array("lead" => $lead));
?>
<?php endif; ?>
<div class="footer-margin"></div>