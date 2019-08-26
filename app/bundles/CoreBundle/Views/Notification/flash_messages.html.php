<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!isset($dismissible)) {
    $dismissible = '';
}

if (!isset($alertType)) {
    $alertType = 'growl';
}

$alertClasses = ($alertType == 'growl') ?
    ['notice' => 'alert-growl',   'warning' => 'alert-growl',   'error' => 'alert-growl'] :
    ['notice' => 'alert-success', 'warning' => 'alert-warning', 'error' => 'alert-danger'];

if (empty($flashes)) {
    $flashes = $app->getSession() ? $view['session']->getFlashes() : [];
}
?>
<?php foreach ($flashes as $type => $messages): ?>
<?php $message = (is_array($messages)) ? $messages[0] : $messages; ?>
<div class="alert <?php echo $alertClasses[$type].$dismissible; ?> alert-new">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"><i class="fa fa-times"></i></button>
    <span><?php echo $message; ?></span>
</div>
<?php endforeach; ?>
