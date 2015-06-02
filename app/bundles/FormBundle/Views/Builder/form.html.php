<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$formName = \Mautic\CoreBundle\Helper\InputHelper::alphanum($form->getName());
$fields   = $form->getFields();
$required = array();
?>

<?php echo $view->render($theme.'MauticFormBundle:Builder:style.html.php', array('form' => $form)); ?>

<form autocomplete="off" role="form" method="post" action="<?php echo $view['router']->generate('mautic_form_postresults', array('formId' => $form->getId()), true); ?>" id="mauticform_<?php echo $formName ?>" onsubmit="return MauticForm_<?php echo $formName; ?>.validateForm();">
    <div class="mauticform-error" id="mauticform_<?php echo $formName ?>_error"></div>
    <div class="mauticform-message" id="mauticform_<?php echo $formName ?>_message"></div>
    <?php foreach ($fields as $f):
        if ($f->isCustom()):
            $params = $f->getCustomParameters();
            $template = $params['template'];
        else:
            $template = 'MauticFormBundle:Field:' . $f->getType() . '.html.php';
        endif;

        echo $view->render($theme.$template, array('field' => $f->convertToArray(), 'id' => $f->getAlias()));
    endforeach; ?>

    <input type="hidden" name="mauticform[formid]" value="<?php echo $form->getId(); ?>" />
    <input type="hidden" name="mauticform[return]" id="mauticform_<?php echo $formName ?>_return" value="" />
</form>

<?php echo $view->render($theme.'MauticFormBundle:Builder:script.html.php', array('form' => $form)); ?>