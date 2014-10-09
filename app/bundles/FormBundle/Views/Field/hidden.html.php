<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$inputAttr = 'id="mauticform_input_' . $field['alias'] . '" name="mauticform['. $field['alias'] .']" value="' . $field['defaultValue'] . '" ' . $field['inputAttributes'];
if (strpos($inputAttr, 'class') === false)
    $inputAttr .= ' class="mauticform_input"';

if (!empty($inForm))
    $inputAttr .= ' disabled="disabled"';

$containerClass = (!empty($deleted)) ? ' bg-danger' : '';
?>

<div class="mauticform-row mauticform-hidden mauticform-row-<?php echo $field['alias'].$containerClass; ?>" id="mauticform_<?php echo $id; ?>">
    <?php
    if (!empty($inForm)):
        echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
            'deleted' => (!empty($deleted)) ? $deleted : false,
            'id'      => $id
        ));
    ?>
        <label class="text-muted"><?php echo $field['label']; ?></label>
    <?php endif; ?>
    <input <?php echo $inputAttr; ?> type="hidden" />
</div>
