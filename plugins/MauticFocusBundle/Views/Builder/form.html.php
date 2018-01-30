<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
use Mautic\CoreBundle\Helper\InputHelper;

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

                return false;
            }
        }
    </script>
<?php endif; ?>

<?php
$formExtra = <<<EXTRA
<input type="hidden" name="mauticform[focusId]" id="mauticform{$formName}_focus_id" value="$focusId"/>
EXTRA;

echo $view->render('MauticFormBundle:Builder:form.html.php', [
        'form'      => $form,
        'formExtra' => $formExtra,
        'action'    => ($preview) ? '#' : null,
        'suffix'    => '_focus',
    ]
);
?>

<!-- END FOCUS FORM -->