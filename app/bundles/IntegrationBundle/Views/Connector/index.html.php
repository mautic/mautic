<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'connector');
$view['slots']->set("headerTitle", $view['translator']->trans('mautic.integration.connector.header.index'));
?>

<div class="panel panel-default bdr-t-wdh-0 mb-0">
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>
</div>
