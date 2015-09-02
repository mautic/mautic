<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$formName = strtolower(\Mautic\CoreBundle\Helper\InputHelper::alphanum($form->getName()));
$fields   = $form->getFields();
$required = array();
?>

<?php echo $view->render($theme.'MauticFormBundle:Builder:script.html.php', array('form' => $form, 'formName' => $formName)); ?>

<?php if ($form->getRenderStyle()) echo $view->render($theme.'MauticFormBundle:Builder:style.html.php', array('form' => $form, 'formName' => $formName)); ?>

<div id="mauticform_wrapper_<?php echo $formName ?>" class="mauticform_wrapper">
    <form autocomplete="false" role="form" method="post" action="<?php echo $view['router']->generate('mautic_form_postresults', array('formId' => $form->getId()), true); ?>" id="mauticform_<?php echo $formName ?>" data-mautic-form="<?php echo $formName ?>">
        <div class="mauticform-error" id="mauticform_<?php echo $formName ?>_error"></div>
        <div class="mauticform-message" id="mauticform_<?php echo $formName ?>_message"></div>
        <div class="mauticform-innerform">
<?php
foreach ($fields as $f):
    if ($f->isCustom()):
        $params = $f->getCustomParameters();
        $template = $params['template'];
    else:
        $template = 'MauticFormBundle:Field:' . $f->getType() . '.html.php';
    endif;

    echo $view->render($theme.$template, array('field' => $f->convertToArray(), 'id' => $f->getAlias(), 'formName' => $formName));
endforeach;
?>


            <input type="hidden" name="mauticform[formId]" id="mauticform_<?php echo $formName ?>_id" value="<?php echo $form->getId(); ?>" />
            <input type="hidden" name="mauticform[return]" id="mauticform_<?php echo $formName ?>_return" value="" />
            <input type="hidden" name="mauticform[formName]" id="mauticform_<?php echo $formName ?>_name" value="<?php echo $formName; ?>" />

        </div>
    </form>
</div>