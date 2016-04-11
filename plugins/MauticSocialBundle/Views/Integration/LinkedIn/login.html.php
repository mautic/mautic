<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultInputClass = 'button';
$containerType = 'div-wrapper';

include __DIR__ . '/../../../../../app/bundles/FormBundle/Views/Field/field_helper.php';

$action = $app->getRequest()->get('objectAction');
$settings = $field['properties'];

$width = (!empty($settings['width'])) ? $settings['width'].'px' : '252px';
$buttonLabel = (!empty($settings['buttonLabel'])) ? $settings['buttonLabel'] : '';
$authURL=(!empty($settings['authUrl'])) ? $settings['authUrl'] : '';

$inputName = 'mauticform[' . $field['alias'] . '_LinkedIn]';
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
  
  function openOAuthWindow(authUrl){
	  if (authUrl) {
          var generator = window.open(authUrl, 'integrationauth', 'height=500,width=500');
          	  if (!generator || generator.closed || typeof generator.closed == 'undefined') {
            	    alert('popupmessage');
            }
      }       
  }

  function fillinForm(response){
  	var elements = document.getElementById("mauticform_{$formName}").elements;
  	var field, fieldName;
  
	for (var i = 0, element; element = elements[i++];) {
		field = element.name
		fieldName= field.replace("mauticform[","");
		fieldName= fieldName.replace("]","");
			 
		values = JSON.parse(JSON.stringify(response));
		for(var key in values) {
		if(key!='id' && fieldName==key) {
			var element = document.getElementsByName("mauticform["+key+"]");
			element[0].value = values[key];
		}
	    }	
	}
}
JS;

$html = <<<HTML
	<style>
	.fb-login{
		position: relative;
		z-index: 10;
		margin-right: 10px;
		white-space: nowrap;
		font-size: 14px;
		width: {$width};
		cursor: pointer;
		background-color: #047bb6;
		border-color: rgba(4, 123, 182, 0.72) #0475ae #045b87;
		border: 1px solid;
		border-radius: 2px;
		box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
		box-sizing: content-box;
		-webkit-font-smoothing: antialiased;
		font-weight: bold;
		text-align: center;
		vertical-align: middle;
		color: #fff;
		padding:10px;
    	text-shadow: 0 -1px 0 rgba(0, 0, 0, .2);
	}
	</style>
	<div $containerAttr>{$formButtons}{$label}
		<a onclick="openOAuthWindow('{$authURL}')" class="fb-login">{$buttonLabel}</a>
	</div>
HTML;
?>
<script>
	<?php echo $js; ?>
</script>
<?php echo $html; ?>

