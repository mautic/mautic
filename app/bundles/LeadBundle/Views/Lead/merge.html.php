<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="bundle-form">

    <label class="control-label required" for="list-search"><?php echo $view['translator']->trans('mautic.lead.merge.search'); ?></label>
    

    <?php echo $view->render('MauticCoreBundle:Helper:search.html.php', array(
                    'searchId'    => (empty($searchId)) ? null : $searchId,
                    'searchValue' => $searchValue,
                    'action'      => $currentRoute,
                    'searchHelp'  => false,
                    'target'      => '.bundle-form',
                    'tmpl'        => (empty($tmpl)) ? null : $tmpl
                )); ?>


    <?php echo $view['form']->start($form); ?>

    <?php echo $view['form']->end($form); ?>


</div>