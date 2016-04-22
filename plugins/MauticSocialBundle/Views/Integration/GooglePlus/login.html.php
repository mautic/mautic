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

$settings = $field['properties'];

$width = (!empty($settings['width'])) ? $settings['width'] . 'px' : '252px';
$buttonLabel = (!empty($settings['buttonLabel'])) ? $settings['buttonLabel'] : '';
$authURL = (isset($settings['authUrl']) and !empty($settings['authUrl'])) ? $settings['authUrl'] : false;

$inputName = 'mauticform[' . $field['alias'] . '_GooglePlus]';
$name = ' name="' . $inputName . '"';
$formName = str_replace("_", "", $formName);
$formButtons = (!empty($inForm)) ? $view->render(
    'MauticFormBundle:Builder:actions.html.php',
    array(
        'deleted' => false,
        'id' => $id,
        'formId' => $formId,
        'formName' => $formName,
        'disallowDelete' => false
    )
) : '';

$label = (!$field['showLabel'])
    ? ''
    : <<<HTML
<label $labelAttr>{$view->escape($field['label'])}</label>
HTML;

$js = <<<JS
	function openOAuthWindow(authUrl){
	  if (authUrl) {
          var generator = window.open(authUrl, 'integrationauth', 'height=500,width=500');
		 
          if (!generator || generator.closed || typeof generator.closed == 'undefined') {
                alert('popupmessage');
            }
      }       
    }
    function fillInForm(response){
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
if (!$authURL) {
    $html = <<<HTML
<div $containerAttr>{$formButtons}{$label} {$view['translator']->trans('mautic.integration.enabled')}</div>
HTML;
} else {
    $html = <<<HTML
<style>
	.g-plus-login{
		position: relative;
		z-index: 10;
		white-space: nowrap;
		font-size: 14px;
		width: {$width};
		cursor: pointer;
		background-color: #cc0800;
		border-color: #c60a00;
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
		<div  class="g-plus-login"><a onclick="openOAuthWindow('{$authURL}')">{$buttonLabel}</a></div>
	</div>
HTML;
}
?>
<?php
if ($authURL) {

    ?>
    <script>
        <?php echo $js; ?>
    </script>
<?php } ?>
<?php
echo $html;
?>
