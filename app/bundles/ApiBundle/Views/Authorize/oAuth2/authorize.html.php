<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticUserBundle:Security:base.html.php');
$view['slots']->set('header', $view['translator']->trans('mautic.api.oauth.header'));
$name = $client->getName();
$msg  = (!empty($name)) ? $view['translator']->trans('mautic.api.oauth.clientwithname', ['%name%' => $name]) :
    $view['translator']->trans('mautic.api.oauth.clientnoname');
?>
<h4 class="mb-lg"><?php echo $msg; ?></h4>
<form class="form-login text-center" role="form" name="fos_oauth_server_authorize_form" action="<?php echo $view['router']->path('fos_oauth_server_authorize') ?>" method="post">

<input type="submit" class="btn btn-primary btn-accept" name="accepted" value="<?php echo $view->escape($view['translator']->trans('mautic.api.oauth.accept')); ?>" />
<input type="submit" class="btn btn-danger btn-deny" name="rejected" value="<?php echo $view->escape($view['translator']->trans('mautic.api.oauth.deny')); ?>" />

<?php
echo $view['form']->row($form['client_id']);
echo $view['form']->row($form['response_type']);
echo $view['form']->row($form['redirect_uri']);
echo $view['form']->row($form['state']);
echo $view['form']->row($form['scope']);
echo $view['form']->rest($form);
?>
</form>
