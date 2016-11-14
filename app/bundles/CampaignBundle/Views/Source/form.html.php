<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="bundle-form">
    <div class="bundle-form-header mb-15">
        <h3><?php echo $view['translator']->trans('mautic.campaign.leadsource.header.singular'); ?></h3>
        <h6 class="text-muted"><?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$sourceType.'.tooltip'); ?></h6>
    </div>

    <?php echo $view['form']->start($form); ?>


    <?php echo $view['form']->end($form); ?>
</div>