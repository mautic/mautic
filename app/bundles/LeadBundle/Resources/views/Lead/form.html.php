<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$header = ($lead->getId()) ?
    $view['translator']->trans('mautic.lead.lead.header.edit',
        array('%name%' => $view['translator']->trans($lead->getPrimaryIdentifier()))) :
    $view['translator']->trans('mautic.lead.lead.header.new');

$view['slots']->set('mauticContent', 'lead');
$view["slots"]->set("headerTitle", $header);
?>

<div class="scrollable">
    <?php echo $view['form']->form($form); ?>
    <div class="footer-margin"></div>
</div>