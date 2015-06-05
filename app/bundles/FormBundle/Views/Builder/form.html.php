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

<?php echo $view->render($theme.'MauticFormBundle:Builder:style.html.php', array('form' => $form, 'formName' => $formName)); ?>

<div id="mauticform_wrapper_<?php echo $formName ?>" class="mauticform_wrapper">
    <form autocomplete="off" target="mauticiframe_<?php echo $formName; ?>" role="form" method="post" action="<?php echo $view['router']->generate('mautic_form_postresults', array('formId' => $form->getId()), true); ?>" id="mauticform_<?php echo $formName ?>" onsubmit="return MauticSDK.validateForm('<?php echo $formName; ?>');">
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

    echo $view->render($theme.$template, array('field' => $f->convertToArray(), 'id' => $f->getAlias()));
endforeach;
?>


            <input type="hidden" name="mauticform[formId]" id="mauticform_<?php echo $formName ?>_id" value="<?php echo $form->getId(); ?>" />
            <input type="hidden" name="mauticform[return]" id="mauticform_<?php echo $formName ?>_return" value="" />
            <input type="hidden" name="mauticform[formName]" id="mauticform_<?php echo $formName ?>_name" value="<?php echo $formName; ?>" />
            <input type="hidden" name="mauticform[messenger]" id="mauticform_<?php echo $formName ?>_messenger" value="1" />

        </div>
    </form>
    <iframe name="mauticiframe_<?php echo $formName; ?>" id="mauticiframe_<?php echo $formName; ?>" style="display: none; margin: 0; padding: 0; border: none; width: 0; height: 0"></iframe>
</div>