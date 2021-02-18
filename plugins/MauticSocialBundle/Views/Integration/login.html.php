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

$script = '<script src="'.$view['router']->url('mautic_social_js_generate', ['formName' => $formName], true)
    .'" type="text/javascript" charset="utf-8" async="async"></script>';

$html = <<<HTML
	<div $containerAttr>{$formButtons}{$label}
HTML;
?>
<?php echo $script; ?>

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
