<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


$defaultInputClass = 'hidden';
$containerType     = 'div-wrapper';

include __DIR__.'/../../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$settings = $field['properties'];

$locale   = $app->getRequest()->getLocale();
$clientId = (!empty($settings['clientId'])) ? $settings['clientId'] : '';
$action   = $app->getRequest()->get('objectAction');


$socialProfile = (!empty($settings['socialProfile'])) ? $settings['socialProfile'] : '';

$socialHiddenFields = explode(',', $socialProfile);
$inputName          = 'mauticform['.$field['alias'].'_GooglePlus]';
$name               = ' name="'.$inputName.'"';
$formName           = str_replace("_", "", $formName);
$formButtons        = (!empty($inForm)) ? $view->render(
    'MauticFormBundle:Builder:actions.html.php',
    array(
        'deleted'        => false,
        'id'             => $id,
        'formId'         => $formId,
        'formName'       => $formName,
        'disallowDelete' => false
    )
) : '';

$label = (!$field['showLabel'])
    ? ''
    : <<<HTML
<label $labelAttr>{$view->escape($field['label'])}</label>
HTML;

$js   = <<<JS
	var isLive='{$action}';
	
	function onSuccess(resp) {
    	gapi.client.load('plus', 'v1', apiClientLoaded);
	}

  /**
   * Sets up an API call after the Google API client loads.
   */
  	function apiClientLoaded() {
    	gapi.client.plus.people.get({userId: 'me'}).execute(fillinForm);
  	}

	function onFailure(error) {
		console.log(error);
	}

	function renderButton() {
		gapi.signin2.render('mautic-googleplussignin', {
			'scope': 'email',
			'width': 240,
			'height': 50,
			'longtitle': true,
			'theme': 'dark',
			'onsuccess': onSuccess,
			'onfailure': onFailure
		});
	}
	function fillinForm(response){
  		var elements = document.getElementById("mauticform_{$formName}").elements;
  		var field, fieldName;
  
		for (var i = 0, element; element = elements[i++];) {
			field = element.name
			fieldName= field.replace("mauticform[","");
			fieldName= fieldName.replace("]","");
			
			if (response && !response.error) {
				values = JSON.parse(JSON.stringify(response));
				
				
				for(var key in values) {
					if(key.indexOf(fieldName) > -1){
						var element = document.getElementsByName("mauticform["+fieldName+"]");
						console.log(values[key][0]['value']);
						element[0].value = values[key][0]['value'];
					}
				}
			}
		}
		if (response && !response.error) {
		var element = document.getElementsByName("{$inputName}");
			element[0].value = JSON.stringify(response);
		}
	}

JS;
$html = <<<HTML
	
	<div $containerAttr>{$formButtons}{$label}
		<meta name="google-signin-client_id" content="{$clientId}">
		<div id="mautic-googleplussignin"></div>	 
		<input type="hidden" value="" {$inputId} {$name} {$inputAttr}  >
	</div>
HTML;
?>
<script>
    <?php echo $js; ?>
</script>
<?php
echo $html;
?>
<script src="https://apis.google.com/js/client:platform.js?onload=renderButton" async defer></script>
