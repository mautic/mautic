<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = 'button';
$containerType     = 'button-wrapper';
include __DIR__ . '/field_helper.php';

$buttonType = (isset($properties['type'])) ? $properties['type'] : 'submit';
?>

<div <?php echo $containerAttr; ?>>
    <?php
    if (!empty($inForm))
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted'     => false,
            'id'          => $id,
            'formId'      => $formId,
            'disallowDelete' => true
        ));
    ?>
    <button type="<?php echo $buttonType; ?>" name="mauticform[<?php echo $field['alias']; ?>]" <?php echo $inputAttr; ?> value="1"><?php echo $field['label']; ?></button>
</div>