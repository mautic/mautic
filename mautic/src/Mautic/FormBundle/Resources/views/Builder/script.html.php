<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
            var i = 0;
            while (!optionsValid && i < elOptions.length) {
                if (elOptions[i].checked) optionsValid = true;
                i++;
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
        <?php foreach ($fields as $f):
        if ($f->isRequired()):
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
        ?>

        var valid = (elForm.elements["<?php echo $name; ?>"].value != '');
        <?php
        break;
        case 'radiogrp':
        case 'checkboxgrp':
            if ($type == 'checkboxgrp') $name .= '[]';
        ?>

        var elOptions = elForm.elements["<?php echo $name; ?>"];
        var valid = validateOptions(elOptions);
        <?php
        break;
        case 'email':
        ?>

        var valid = validateEmail(elForm.elements["<?php echo $name; ?>"].value);
        <?php
        break;
        default:
        ?>

        var valid = (elForm.elements["<?php echo $name; ?>"].value != '');
        <?php
        break;
        endswitch;
        ?>
        markError('<?php echo $id; ?>', valid);
        if (!valid) formValid = false;
        <?php
        endif;
        endforeach;
        ?>

        if (formValid) {
            document.getElementById('mauticform_<?php echo $formName ?>_return').value = document.URL;
        }
        return formValid;
    },
    checkMessages: function() {
        var query = {};
        location.search.substr(1).split("&").forEach(function(item) {query[item.split("=")[0]] = item.split("=")[1]});
        if (typeof query.mauticError !== 'undefined') {
            var errorContainer = document.getElementById('mauticform_<?php echo $formName; ?>_error');
            errorContainer.innerHTML = decodeURIComponent(query.mauticError);
        } else if (typeof query.mauticMessage !== 'undefined') {
            var messageContainer = document.getElementById('mauticform_<?php echo $formName; ?>_message');
            messageContainer.innerHTML = decodeURIComponent(query.mauticMessage);
        }
    }
}
MauticForm_<?php echo $formName; ?>.checkMessages();
</script>