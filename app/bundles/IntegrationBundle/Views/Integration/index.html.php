<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'integration');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.integration.header.index'));
$view['slots']->set('searchUri', $view['router']->generate('mautic_integration_index', array('page' => $page)));
$view['slots']->set('searchString', $app->getSession()->get('mautic.integration.filter'));
$view['slots']->set('searchHelp', $view['translator']->trans('mautic.integration.help.searchcommands'));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <?php echo $view->render('MauticCoreBundle:Helper:listactions.html.php', array(
        'searchValue' => $searchValue,
        'action'      => $currentRoute,
        'menuLink'    => 'mautic_integration_index',
        'langVar'     => 'integration',
        'routeBase'   => 'integration'
    )); ?>
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
