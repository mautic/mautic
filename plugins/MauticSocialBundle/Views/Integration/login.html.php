<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputClass = 'button';
$containerType     = 'div-wrapper';

include __DIR__.'/../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$action   = $app->getRequest()->get('objectAction');
$settings = $field['properties'];

$integrations = (isset($settings['integrations']) and !empty($settings['integrations'])) ? explode(',', substr($settings['integrations'], 0, -1))
    : [];

$formName    = str_replace('_', '', $formName);
$formButtons = (!empty($inForm)) ? $view->render(
    'MauticFormBundle:Builder:actions.html.php',
    [
        'deleted'        => false,
        'id'             => $id,
        'formId'         => $formId,
        'formName'       => $formName,
        'disallowDelete' => false,
    ]
) : '';

$label = (!$field['showLabel'])
    ? ''
    : <<<HTML
<label $labelAttr>{$view->escape($field['label'])}</label>
HTML;

$js = <<<JS
    var isLive='{$action}';

    function openOAuthWindow(authUrl){
        if (authUrl) {
            var generator = window.open(authUrl, 'integrationauth', 'height=500,width=500');
            if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert(mauticLang.popupBlockerMessage);
            }
        }
    }
    
    function postAuthCallback(response){
        var elements = document.getElementById("mauticform_{$formName}").elements;
        var field, fieldName;
        values = JSON.parse(JSON.stringify(response));
        
        for (var i = 0, element; element = elements[i++];) {
            field = element.name
            fieldName = field.replace("mauticform[","");
            fieldName = fieldName.replace("]","");
            var element = document.getElementsByName("mauticform["+fieldName+"]");
            
            // Remove underscores, dashes, and f_ prefix for comparison
            fieldName = fieldName.replace("f_", "").replace(/_/g,"").replace(/-/g, "");
            for(var key in values) {
                var compareKey = key.replace(/_/g,"").replace(/-/g, "");
                if (key != 'id' && (key.indexOf(fieldName) >= 0 || fieldName.indexOf(key) >= 0) && element[0].value == "") {
                    if (values[key].constructor === Array && values[key][0].value) {
                        element[0].value = values[key][0].value;
                    } else {
                        element[0].value = values[key];
                    }
                    
                    break;
                }
            }
        }
    }
JS;
$html = <<<HTML
	<div $containerAttr>{$formButtons}{$label}
HTML;
?>
<script>
    <?php echo $js; ?>
</script>

<?php
echo $html;
foreach ($integrations as $integration) {
    if (isset($settings['buttonImageUrl'])) {
        echo '<a href="#" onclick="openOAuthWindow(\''.$settings['authUrl_'.$integration].'\')"><img src="'.$settings['buttonImageUrl'].'btn_'
            .$integration.'.png"></a>';
    }
}

?>
</div>
