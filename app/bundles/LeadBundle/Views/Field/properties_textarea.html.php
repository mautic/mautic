<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$type     = 'textarea';
$value    = (isset($value)) ? $value : '';
    $html = str_replace(['properties_'.$type.'_template', 'leadfield_properties', 'leadfield[properties]'], ['properties', 'leadfield_properties_template', 'leadfield[properties][allowHtml]'], $textareaTemplate);
?>

<div class="<?php echo $type; ?>">
    <?php echo $html; ?>
</div>