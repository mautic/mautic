<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
use \Mautic\CoreBundle\Helper\InputHelper;

$formName = '_'.strtolower(
        InputHelper::alphanum(
            InputHelper::transliterate(
                $form->getName()
            )
        ).'_focus'
    );
$jsFormName = ltrim($formName, '_');
$fields     = $form->getFields();
$required   = [];
?>

<!-- START FOCUS FORM -->

<?php
if (empty($preview)):
    echo $view->render('MauticFormBundle:Builder:script.html.php', ['form' => $form, 'formName' => $formName]); ?>

    <script>
        var MauticFocusHandler = function (messageType, message) {
            // Store the HTML
            var wrapper = document.getElementById('mauticform_wrapper<?php echo $formName ?>');
            var innerForm = wrapper.getElementsByClassName('mauticform-innerform');
            innerForm[0].style.display = "none";

            <?php if ($style == 'page'): ?>
            document.getElementById('mauticform<?php echo $formName ?>_' + messageType).style.fontSize = "2em";
            <?php elseif ($style != 'bar'): ?>
            document.getElementById('mauticform<?php echo $formName ?>_' + messageType).style.fontSize = "1.1em";
            <?php endif; ?>

            var headline = document.getElementsByClassName('mf-headline');
            if (headline.length) {
                headline[0].style.display = "none";

            }
            var tagline = document.getElementsByClassName('mf-tagline');
            if (tagline.length) {
                tagline[0].style.display = "none";
            }

            if (message) {
                document.getElementById('mauticform<?php echo $formName ?>_' + messageType).innerHTML = message;
            }

            setTimeout(function () {
                if (headline.length) {
                    <?php if ($style == 'bar'): ?>
                    headline[0].style.display = "inline-block";
                    <?php else : ?>
                    headline[0].style.display = "block";
                    <?php endif; ?>
                }
                if (tagline.length) {
                    tagline[0].style.display = "inherit";
                }

                innerForm[0].style.display = "inherit";
                document.getElementById('mauticform<?php echo $formName ?>_' + messageType).innerHTML = '';
            }, (messageType == 'error') ? 1500 : 5000);
        }
        if (typeof MauticFormCallback == 'undefined') {
            var MauticFormCallback = {};
        }
        MauticFormCallback["<?php echo $jsFormName; ?>"] = {
            onMessageSet: function (data) {
                if (data.message) {
                    MauticFocusHandler(data.type);
                }
            },
            onErrorMark: function (data) {
                if (data.validationMessage) {
                    MauticFocusHandler('error', data.validationMessage);

                    return true;
                }
            },
            onResponse: function (data) {
                if (data.download) {
                    // Hit the download in the iframe
                    document.getElementById('mauticiframe<?php echo $formName; ?>').src = data.download;

                    // Register a callback for a redirect
                    if (data.redirect) {
                        setTimeout(function () {
                            window.top.location = data.redirect;
                        }, 2000);
                    }

                    return true;
                } else if (data.redirect) {
                    window.top.location = data.redirect;

                    return true;
                }
            }
        }
    </script>

    <div id="mauticform_wrapper<?php echo $formName ?>" class="mauticform_wrapper">
        <form autocomplete="false" role="form" method="post" action="<?php echo $view['router']->url(
            'mautic_form_postresults',
            ['formId' => $form->getId()],
            true
        ); ?>" id="mauticform<?php echo $formName ?>" data-mautic-form="<?php echo $jsFormName; ?>">
            <div class="mauticform-error" id="mauticform<?php echo $formName ?>_error"></div>
            <div class="mauticform-message" id="mauticform<?php echo $formName ?>_message"></div>
            <div class="mauticform-innerform">
                <?php
                endif;

                /** @var \Mautic\FormBundle\Entity\Field $f */
                foreach ($fields as $f):
                    if ($f->isCustom()):
                        $params   = $f->getCustomParameters();
                        $template = $params['template'];
                    else:
                        $template = 'MauticFormBundle:Field:'.$f->getType().'.html.php';
                    endif;

                    // Hide the label and make it a placeholder instead
                    $placeholder = '';
                    if ($f->getType() != 'radiogrp' && $f->getType() != 'checkboxgrp' && $f->showLabel()) {
                        $f->setShowLabel(false);

                        // Show a placeholder instead
                        $properties = $f->getProperties();
                        if (array_key_exists('placeholder', $properties) && empty($properties['placeholder'])) {
                            $properties['placeholder'] = $f->getLabel();
                            $f->setProperties($properties);
                        }
                    }
                    echo $view->render($template, ['field' => $f->convertToArray(), 'id' => $f->getAlias()]);
                endforeach;

                if (empty($preview)):
                ?>

                <input type="hidden" name="mauticform[formId]" id="mauticform<?php echo $formName ?>_id" value="<?php echo $form->getId(); ?>"/>
                <input type="hidden" name="mauticform[return]" id="mauticform<?php echo $formName ?>_return" value=""/>
                <input type="hidden" name="mauticform[formName]" id="mauticform<?php echo $formName ?>_name" value="<?php echo $jsFormName; ?>"/>

                <input type="hidden" name="mauticform[focusId]" id="mauticform<?php echo $formName ?>_focus_id" value="<?php echo $focusId; ?>"/>

            </div>
        </form>
    </div>
<?php endif; ?>

<!-- END FOCUS FORM -->