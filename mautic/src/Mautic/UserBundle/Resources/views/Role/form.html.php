<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$objectId = $form->vars['data']->getId();
if (!empty($objectId)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.user.role.header.edit', array("%name%" => $name));
} else {
    $header = $view['translator']->trans('mautic.user.role.header.new');
}
$view["slots"]->set("headerTitle", $header);
//populate JS functions only required for page refreshes
$view['slots']->set("jsDeclarations", "Mautic.ajaxifyForms(['role']);\n");
?>

<?php echo $view['form']->form($form); ?>