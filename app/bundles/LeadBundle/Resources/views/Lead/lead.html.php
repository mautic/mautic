<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'lead');
$view["slots"]->set("headerTitle",
    '<span class="span-block">' . $view['translator']->trans($lead->getPrimaryIdentifier())) . '</span><span class="span-block small">' .
    $lead->getSecondaryIdentifier() . '</span>';
?>
<?php $view["slots"]->start("actions"); ?>
<?php if ($security->hasEntityAccess($permissions['lead:leads:editown'], $permissions['lead:leads:editother'], $lead->getOwner())): ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_lead_action', array("objectAction" => "edit", "objectId" => $lead->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_lead_index">
        <?php echo $view["translator"]->trans("mautic.core.form.edit"); ?>
    </a>
</li>
<?php endif; ?>
<?php if ($security->hasEntityAccess($permissions['lead:leads:deleteown'], $permissions['lead:leads:deleteother'], $lead->getOwner())): ?>
<li>
    <a href="<?php echo $this->container->get('router')->generate(
        'mautic_lead_action', array("objectAction" => "delete", "objectId" => $lead->getId())); ?>"
       data-toggle="ajax"
       data-menu-link="#mautic_lead_index">
        <?php echo $view["translator"]->trans("mautic.core.form.delete"); ?>
    </a>
</li>
<?php endif; ?>
<?php $view["slots"]->stop(); ?>

<?php
echo $view->render('MauticLeadBundle:Lead:info.html.php', array("lead" => $lead, 'dateFormats' => $dateFormats));

echo $view->render('MauticLeadBundle:Lead:score_log.html.php', array("lead" => $lead, 'dateFormats' => $dateFormats));

$ipAddresses = count($lead->getIpAddresses());
if (!empty($ipAddresses))
    echo $view->render('MauticLeadBundle:Lead:ip_addresses.html.php', array("lead" => $lead));
?>
<div class="footer-margin"></div>