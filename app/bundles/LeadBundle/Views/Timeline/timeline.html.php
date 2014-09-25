<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:content.html.php');
}

$view['slots']->set('mauticContent', 'lead');
$view['slots']->set('headerTitle',
    '<span class="span-block">' . $view['translator']->trans($lead->getPrimaryIdentifier()) . '</span><span class="span-block small">' .
    $lead->getSecondaryIdentifier() . '</span>');

?>
<div class="scrollable">
    <h1>Coming Soon</h1>
</div>
