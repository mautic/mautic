<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticUserBundle:Security:base.html.php');
$view['slots']->set('header', $view['translator']->trans('mautic.api.oauth.header'));
?>

<form class="form-login" role="form" action="<?php echo $view['router']->generate($route) ?>" method="post">
    <div class="margin-md">
        <label for="username" class="sr-only"><?php echo $view['translator']->trans('mautic.user.auth.form.loginusername'); ?></label>
        <input type="text" id="username" name="_username" class="form-control input-lg" value="<?php echo $last_username ?>" required autofocus placeholder='<?php echo $view['translator']->trans('mautic.user.auth.form.loginusername'); ?>' />
    </div>
    <div class="margin-md">
        <label for="password" class="sr-only"><?php echo $view['translator']->trans('mautic.user.auth.form.loginpw'); ?>:</label>
        <input type="password" id="password" name="_password" class="form-control input-lg" required placeholder='<?php echo $view['translator']->trans('mautic.user.auth.form.loginpw'); ?>' />
    </div>

    <button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $view['translator']->trans('mautic.user.auth.form.loginbtn'); ?></button>
</form>