<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$inputAttr = $field['inputAttributes'];

if (strpos($inputAttr, 'class') === false)
    $inputAttr .= ' class="mauticform-button"';

$properties = $field['properties'];

if (!empty($inForm))
    $inputAttr .= 'disabled="disabled"';

$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="mauticform-row mauticform-button<?php echo $containerClass; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id
        ));
    ?>
    <button type="<?php echo $properties['type']; ?>" name="mauticform[<?php echo $field['alias']; ?>]" <?php echo $inputAttr; ?> value="1"><?php echo $field['label']; ?></button>
</div>