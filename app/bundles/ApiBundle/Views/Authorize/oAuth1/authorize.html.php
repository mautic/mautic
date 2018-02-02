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
$name = $consumer->getName();
$msg  = (!empty($name)) ? $view['translator']->trans('mautic.api.oauth.clientwithname', ['%name%' => $name]) :
    $view['translator']->trans('mautic.api.oauth.clientnoname');
?>
<h4 class="mb-lg"><?php echo $msg; ?></h4>
<form class="form-login text-center" role="form" name="bazinga_oauth_server_authorize" action="<?php echo $view['router']->path('bazinga_oauth_server_authorize') ?>" method="post">
    <input type="hidden" name="oauth_token" value="<?php echo $view->escape($oauth_token); ?>" />
    <input type="hidden" name="oauth_callback" value="<?php echo $view->escape($oauth_callback); ?>" />

    <input type="submit" class="btn btn-primary btn-accept" name="submit_true" value="<?php echo $view->escape($view['translator']->trans('mautic.api.oauth.accept')); ?>" />
    <input type="submit" class="btn btn-danger btn-deny" name="submit_false" value="<?php echo $view->escape($view['translator']->trans('mautic.api.oauth.deny')); ?>" />
</form>
