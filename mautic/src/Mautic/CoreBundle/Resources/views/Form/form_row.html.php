<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$feedbackClass = ($app->getRequest()->getMethod() == 'POST' && !empty($errors)) ? " has-error has-feedback" : "";
?>

<div class="row">
    <div class="form-group col-xs-12 col-md-8 col-lg-4<?php echo $feedbackClass; ?>">
        <?php echo $view['form']->label($form, $label) ?>
        <?php echo $view['form']->widget($form) ?>
        <?php echo $view['form']->errors($form) ?>
    </div>
</div>