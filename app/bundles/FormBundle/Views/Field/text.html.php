<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$containerType     = (isset($type)) ? $type : 'text';
$defaultInputClass = 'input';
include __DIR__ . '/field_helper.php';
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
    <input <?php echo $inputAttr; ?> type="<?php echo $containerType; ?>" />
    <?php if (!empty($validationMessage)): ?>
        <span class="mauticform-errormsg" style="display: none;"><?php echo $validationMessage; ?></span>
    <?php endif; ?>
</div>