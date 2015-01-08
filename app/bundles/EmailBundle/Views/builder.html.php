<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:email.html.php");

$view['assets']->addScriptDeclaration("var mauticBasePath = '$basePath';");
$view['assets']->addScriptDeclaration("var mauticAjaxUrl = '" . $view['router']->generate("mautic_core_ajax") . "';");
$view['assets']->addCustomDeclaration($view['assets']->getSystemScripts(true, true));
$view['assets']->addScript('app/bundles/EmailBundle/Assets/builder/builder.js');
$view['assets']->addStylesheet('app/bundles/EmailBundle/Assets/builder/builder.css');

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    $view['slots']->set($slot, "<div id=\"slot-{$slot}\" class=\"mautic-editable\" contenteditable=true data-placeholder=\"{$view['translator']->trans('mautic.page.builder.addcontent')}\">{$value}</div>");
}

//add builder toolbar
$view['slots']->start('builder');?>
<input type="hidden" id="builder_entity_id" value="<?php echo $email->getSessionId(); ?>" />
<?php
$view['slots']->stop();
?>
