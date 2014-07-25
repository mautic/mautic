<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$formName = \Mautic\CoreBundle\Helper\InputHelper::alphanum($form->getName());
$fields   = $form->getFields();
$required = array();
?>
<div class="mauticform-name"><?php echo $form->getName(); ?></div>
<?php if ($description = $form->getDescription()): ?>
<p class="mauticform-description"><?php echo $description; ?></p>
<?php endif; ?>
<form autocomplete="off" role="form" method="post" action="<?php echo $view['router']->generate('mautic_form_postresults', array('formId' => $form->getId()), true); ?>" id="mauticform_<?php echo $formName ?>" onsubmit="return MauticForm_<?php echo $formName; ?>.validateForm();">
<div class="mauticform-error" id="mauticform_<?php echo $formName ?>_error"></div>
<div class="mauticform-message" id="mauticform_<?php echo $formName ?>_message"></div>
<?php
foreach ($fields as $f):
if ($f->isCustom()):
$params = $f->getCustomParameters();
$template = $params['template'];
else:
$template = 'MauticFormBundle:Field:' . $f->getType() . '.html.php';
endif;
?>
<?php echo $view->render($template, array('field' => $f->convertToArray(), 'id' => $f->getAlias())); ?>
<?php endforeach; ?>
<div class="mauticform-row mauticform-hidden">
    <input type="hidden" name="mauticform[formid]" value="<?php echo $form->getId(); ?>" />
    <input type="hidden" name="mauticform[return]" id="mauticform_<?php echo $formName ?>_return" value="" />
</div>
</form>