<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = 'hidden';
$containerType = 'div-wrapper';

include __DIR__ . '/../../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$action = $app->getRequest()->get('objectAction');
$settings = $field['properties'];

$clientId = (!empty($settings['clientId'])) ? $settings['clientId'] : '';
$locale = $app->getRequest()->getLocale();

$maxRows = (!empty($settings['maxRows'])) ? intval($settings['maxRows']) : 1;
$size = (!empty($settings['size'])) ? $settings['size'] : 'medium';
$showFaces = (!empty($settings['showFaces'])) ? $settings['showFaces'] : 'false';
$autoLogout = (!empty($settings['autoLogout'])) ? $settings['autoLogout'] : 'false';
$socialProfile = (!empty($settings['socialProfile'])) ? $settings['socialProfile'] : '';

$socialHiddenFields = explode(',', $socialProfile);
$inputName = 'mauticform[' . $field['alias'] . '_Facebook]';
$name = ' name="' . $inputName . '"';
$formName = str_replace("_", "", $formName);
$formButtons = (!empty($inForm)) ? $view->render('MauticFormBundle:Builder:actions.html.php',
	array(
		'deleted' => false,
		'id' => $id,
		'formId' => $formId,
		'formName' => $formName,
		'disallowDelete' => false)
) : '';

$label = (!$field['showLabel']) ? '' : <<<HTML
<label $labelAttr>{$view->escape($field['label'])}</label>
HTML;

$js = <<<JS
  var isLive='{$action}';
  function statusFacebookIntegrationChangeCallback(response) {
    console.log(response);
    if (response.status === 'connected') {
      // Logged into your app and Facebook.
    	fillinForm();	
    } else if (response.status === 'not_authorized') {
		var element = document.getElementsByName("{$inputName}");
      	element[0].value = '';
    } else {
		var element = document.getElementsByName("{$inputName}");
      	element[0].value = '';
    }
  }

  function checkLoginState() {
    FB.getLoginStatus(function(response) {
 
      statusFacebookIntegrationChangeCallback(response);
    });
  }

  window.fbAsyncInit = function() {
  FB.init({
    appId      : '{$clientId}',
    cookie     : true,  // enable cookies to allow the server to access 
                        // the session
    xfbml      : true,  // parse social plugins on this page
    version    : 'v2.5' // use graph api version 2.5
  });

  FB.getLoginStatus(function(response) {
    if(isLive!='edit' && isLive != 'new'){
    
    	statusFacebookIntegrationChangeCallback(response);
    }
  });

  };

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/{$locale}/sdk.js#xfbml=1&version=v2.5";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));

  function fillinForm(){
  	var elements = document.getElementById("mauticform_{$formName}").elements;
  	var field, fieldName;
  
	for (var i = 0, element; element = elements[i++];) {
		field = element.name
		fieldName= field.replace("mauticform[","");
		fieldName= fieldName.replace("]","");
		
		FB.api('/me',{fields:fieldName}, function(response) {
			 if (response && !response.error) {
				values = JSON.parse(JSON.stringify(response));
				for(var key in values) {
				if(key!='id') {
					var element = document.getElementsByName("mauticform["+key+"]");
					element[0].value = values[key];
				}
			 	}
			}
		});
	}
	
	FB.api('/me',{fields:'{$socialProfile}'}, function(response) {
			
		//the user has authorised this app
		var element = document.getElementsByName("{$inputName}");
			if (response && !response.error) {
					
				element[0].value = JSON.stringify(response);
			}
		});
}
JS;

$html = <<<HTML
	
	<div $containerAttr>{$formButtons}{$label}
		<fb:login-button scope="public_profile,email" data-max-rows="{$maxRows}" data-size="{$size}" data-show-faces="{$showFaces}" data-auto-logout-link="{$autoLogout}" onlogin="checkLoginState();">
		</fb:login-button>
		<input type="hidden" value="" {$inputId} {$name} {$inputAttr}  >
	</div>
HTML;
?>
<div id="fb-root"></div>
<script>
	<?php echo $js; ?>
</script>
<?php echo $html; ?>
