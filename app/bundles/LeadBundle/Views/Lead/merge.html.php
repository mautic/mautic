<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$searchValue = (empty($searchValue)) ? '' : $searchValue;
//$action = $view['action']
$maxLeadId = 100;
//include 'action_button_helper.php';
/*
    <div class="page-list">
        <?php $view['slots']->output('_content'); ?>
    </div>

    <script>         Mautic.setModeratedInterval('leadListLiveUpdate', 'updateLeadList', 5000);</script>



*/



?>

<div class="bundle-form">

    <div id="form-container">

    <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array(
                    'searchId'    => (empty($searchId)) ? null : $searchId,
                    'searchValue' => $searchValue,
                    'action'      => $currentRoute,
                    'searchHelp'  => false,
                    'target'      => '#form-container',
                    'tmpl'        => (empty($tmpl)) ? null : $tmpl
                )); ?>
    <?php echo $view['form']->start($form); ?>

    <?php echo $view['form']->end($form); ?>
    

    </div>

</div>