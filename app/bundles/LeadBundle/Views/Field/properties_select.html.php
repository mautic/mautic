<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$type  = (!empty($isLookup)) ? 'lookup' : 'select';
$value = (isset($value)) ? $value : '';
if (!isset($form) || !$form->vars['value']) {
    $html = str_replace(['properties_'.$type.'_template', 'leadfield_properties'], ['properties', 'leadfield_properties_template'], $selectTemplate);
} else {
    $html = $view['form']->row($form);
}
?>

<div class="<?php echo $type; ?>">
    <?php echo $html; ?>
</div>