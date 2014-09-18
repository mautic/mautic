<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="bundle-form">
    <div class="bundle-form-header">
        <h3><?php echo $fieldHeader; ?></h3>
    </div>

    <?php echo $view['form']->form($form); ?>
</div>