<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = $containerType = 'freetext';
include __DIR__ . '/field_helper.php';

$text = $view->escape($properties['text']);
?>

<div <?php echo $containerAttr; ?>>
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id,
            'formId'  => $formId
        ));
    ?>
    <?php if ($field['showLabel']): ?>
    <h3 <?php echo $labelAttr; ?> id="mauticform_label_<?php echo $field['alias'] ?>" for="mauticform_input_<?php echo $field['alias'] ?>"><?php echo $view->escape($field['label']); ?></h3>
    <?php endif; ?>
    <div <?php echo $inputAttr; ?> id="mauticform_input_<?php echo $field['alias'] ?>">
        <?php echo html_entity_decode($text); ?>
    </div>
</div>
