<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$labelAttr = $field['labelAttributes'];
$inputAttr = $field['inputAttributes'];

if (strpos($labelAttr, 'class') === false)
    $labelAttr .= ' class="mauticform-label"';

$properties = $field['properties'];
$text       = $view->escape($properties['text']);

$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="mauticform-row mauticform-freetext mauticform-row-<?php echo $field['alias'].$containerClass; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id,
            'formId'  => $formId
        ));
    ?>
    <?php if ($field['showLabel']): ?>
    <label <?php echo $labelAttr; ?> id="mauticform_label_<?php echo $field['alias'] ?>" for="mauticform_input_<?php echo $field['alias'] ?>"><?php echo $view->escape($field['label']); ?></label>
    <?php endif; ?>
    <span <?php echo $inputAttr; ?> id="mauticform_input_<?php echo $field['alias'] ?>">
        <?php echo $text; ?>
    </span>
</div>
