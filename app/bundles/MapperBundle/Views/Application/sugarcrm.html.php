<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set("headerTitle", $appIntegration->getAppName());
$view['slots']->set('mauticContent', $appIntegration->getAppName());
?>
<form action="<?php echo $appIntegration->getSaveLink(); ?>" name="<?php echo $formName; ?>" method="post" role="form">
    <div class="panel-body">
        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_url" class="control-label">Sugar CRM URL</label>
                <input type="text" class="form-control" name="url" id="sugarcrm_url">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_consumer_key" class="control-label">API Consumer Key</label>
                <input type="text" class="form-control" name="clientKey" id="sugarcrm_consumer_key">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_consumer_secret" class="control-label">API Consumer Secret</label>
                <input type="text" class="form-control" name="clientSecret" id="sugarcrm_consumer_secret">
            </div>
        </div>

        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_callback" class="control-label">Callback</label>
                <input type="text" class="form-control" readonly value="<?php echo $appIntegration->getCallbackLink(); ?>">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_username" class="control-label">Username</label>
                <input type="text" class="form-control" name="username" id="sugarcrm_username">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12">
                <label for="sugarcrm_password" class="control-label">Password</label>
                <input type="password" class="form-control" name="password" id="sugarcrm_password">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-xs-12 col-sm-8 col-md-6">
                <button class="btn btn-primary" type="submit">Authorize App</button>
            </div>
        </div>
    </div>
</form>
