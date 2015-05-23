<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = $containerType = 'hidden';

include __DIR__ . '/field_helper.php';
?>

<?php if (!empty($inForm)): ?>
<div <?php echo $containerAttr; ?>>
    <?php
    echo $view->render('MauticFormBundle:Builder:actions.html.php', array(
        'deleted' => (!empty($deleted)) ? $deleted : false,
        'id'      => $id,
        'formId'  => $formId
    ));
    ?>
    <label class="text-muted"><?php echo $field['label']; ?></label>
</div>
<?php else: ?>
<input <?php echo $inputAttr; ?> type="hidden" />
<?php endif; ?>