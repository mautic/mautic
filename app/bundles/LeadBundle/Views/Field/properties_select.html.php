<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$value = (isset($value)) ? $value : "";
if (!isset($form) || !$form->vars['value']) {
    $html = str_replace(['properties_select_template', 'leadfield_properties'], ['properties', 'leadfield_properties_template'], $selectTemplate);
} else {
    $html = $view['form']->row($form);
}

?>

<div class="select">
    <?php echo $html; ?>
</div>