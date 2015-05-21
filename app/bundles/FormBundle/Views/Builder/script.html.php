<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$fields   = $form->getFields();
$formName = \Mautic\CoreBundle\Helper\InputHelper::alphanum($form->getName());
?>

<script type="text/javascript">
var MauticForm_<?php echo $formName; ?> = {
    formId: "mauticform_<?php echo $formName; ?>",
    validateForm: function () {
        var formValid = true;

        function validateOptions(elOptions) {
            var optionsValid = false;

            if (elOptions.length == undefined) {
                elOptions = [ elOptions ];
            }

            for (var i=0; i < elOptions.length; i++) {
                if (elOptions[i].checked) {
                    optionsValid = true;
                    break;
                }
            }

            return optionsValid;
        }

        function validateEmail(email) {
            var atpos = email.indexOf("@");
            var dotpos = email.lastIndexOf(".");
            var valid = (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= email.length) ? false : true;
            return valid;
        }

        function markError(containerId, valid) {
            var elContainer = document.getElementById(containerId);
            var elErrorSpan = elContainer.querySelector('.mauticform-errormsg');
            elErrorSpan.style.display = (valid) ? 'none' : '';
        }

        var elForm = document.getElementById(this.formId);
        <?php
        foreach ($fields as $f):
            if ($f->isRequired()):
                echo "\n";

                $name = "mauticform[".$f->getAlias()."]";
                $id   = 'mauticform_' . $f->getAlias();
                $type = $f->getType();

                switch ($type):
                    case 'select':
                    case 'country':
                        $properties = $f->getProperties();
                        $multiple   = $properties['multiple'];
                        if ($multiple)
                            $name .= '[]';

                        echo "        var valid = (elForm.elements[\"$name\"].value != '');\n";

                        break;

                    case 'radiogrp':
                    case 'checkboxgrp':
                        if ($type == 'checkboxgrp') $name .= '[]';

                        echo "        var elOptions = elForm.elements[\"$name\"];\n";
                        echo "        var valid = validateOptions(elOptions);\n";

                        break;

                    case 'email':
                        echo "        var valid = validateEmail(elForm.elements[\"$name\"].value);\n";

                        break;

                    default:
                        echo "        var valid = (elForm.elements[\"$name\"].value != '');\n";

                        break;
                endswitch;

                echo "        markError('$id', valid);\n";
                echo "        if (!valid) formValid = false;\n";

            endif;
        endforeach;
        ?>

        if (formValid) {
            document.getElementById('mauticform_<?php echo $formName ?>_return').value = document.URL;
            if (typeof localStorage !== 'undefined') {
                try {
                    var vars = {};
                    for (var i = 0; i < elForm.elements.length; i++) {
                        var e = elForm.elements[i];

                        if (e.type == 'hidden' || e.type == 'button') {
                            continue;
                        }

                        vars[e.id] = {
                            'id': e.id,
                            'name': e.name,
                            'type': e.type,
                            'value': e.value,
                            'checked': (e.type == 'checkbox' || e.type == 'radio') ? e.checked : false
                        }
                    }

                    localStorage.setItem('<?php echo $formName; ?>', JSON.stringify(vars));
                } catch (err) {}
            }
        }

        return formValid;
    },
    checkMessages: function() {
        var query  = {};

        location.search.substr(1).split("&").forEach(function(item) {query[item.split("=")[0]] = item.split("=")[1]});
        if (typeof query.mauticError !== 'undefined') {
            var errorContainer = document.getElementById('mauticform_<?php echo $formName; ?>_error');
            errorContainer.innerHTML = decodeURIComponent(query.mauticError);

            if (typeof localStorage !== 'undefined') {
                try {
                    var storedData = localStorage.getItem('<?php echo $formName; ?>');
                    if (storedData) {
                        var vars = JSON.parse(storedData);
                        console.log(vars);
                        var key;
                        var el;
                        for (key in vars) {
                            el = vars[key];
                            if (el.type == 'checkbox' || el.type == 'radio') {
                                document.getElementById(el.id).checked = el.checked;
                            } else {
                                document.getElementById(el.id).value = el.value;
                            }
                        }
                    }
                } catch (err) {}
            }
        } else {
            if (typeof query.mauticMessage !== 'undefined') {
                var messageContainer = document.getElementById('mauticform_<?php echo $formName; ?>_message');
                messageContainer.innerHTML = decodeURIComponent(query.mauticMessage);
            }

            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('<?php echo $formName; ?>');
            }
        }
    }
}
MauticForm_<?php echo $formName; ?>.checkMessages();
</script>