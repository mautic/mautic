<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'client');
$id = $form->vars['data']->getId();
if (!empty($id)) {
    $name   = $form->vars['data']->getName();
    $header = $view['translator']->trans('mautic.api.client.header.edit', ['%name%' => $name]);
} else {
    $header = $view['translator']->trans('mautic.api.client.header.new');
}
$view['slots']->set('headerTitle', $header);
?>

<div class="row">
    <div class="pa-md">
        <div class="col-md-6">
            <?php echo $view['form']->form($form); ?>
        </div>
    </div>
</div>