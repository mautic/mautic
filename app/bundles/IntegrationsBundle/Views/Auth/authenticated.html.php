<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:slim.html.php');

$alertClass = $authenticationError ? 'danger' : 'success';

?>
<style>
    #app-content {
        margin: 0;
    }
</style>
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-<?php echo $alertClass; ?> margin" style="margin:20px" role="alert">
            <?php echo $message; ?>
        </div>
    </div>
</div>
<div class="row text-center">
    <button type="button" id="integration_details_authButton" name="integration_details[authButton]" class="btn btn-success btn-lg" onclick="window.close();">
        <i class="fa fa-check"></i>
        <?php echo $view['translator']->trans('mautic.integration.closewindow'); ?>
    </button>
</div>
