<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';

include __DIR__ . '/field_helper.php';

$name = 'mauticform['.$field['alias'].']';
if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
    $name      .= '[]';
}
$inputAttr .= ' name="'.$name.'"';;

if (!isset($list))
    $list = $properties['list'];

if (isset($list['list']))
    $list = $list['list'];
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
    <label <?php echo $labelAttr; ?>><?php echo $view->escape($field['label']); ?></label>
    <?php endif; ?>
    <?php if (!empty($helpMessage)): ?>
    <span class="mauticform-helpmessage"><?php echo $helpMessage; ?></span>
    <?php endif; ?>

    <select <?php echo $inputAttr; ?>>
        <?php if (!empty($properties['empty_value'])): ?>
        <option value=""><?php echo $properties['empty_value']; ?></option>
        <?php endif; ?>
        <?php foreach ($list as $l): ?>
        <?php $selected = ($l === $field['defaultValue']) ? ' selected="selected"' : ''; ?>
        <option value="<?php echo $view->escape($l); ?>"<?php echo $selected; ?>><?php echo $view->escape($l); ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($validationMessage)): ?>
    <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>
