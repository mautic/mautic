<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<script>
    var mauticBasePath    = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl     = '<?php echo $view['router']->generate("mautic_base_index"); ?>';
    var mauticAjaxUrl     = '<?php echo $view['router']->generate("mautic_core_ajax"); ?>';
    var mauticAssetPrefix = '<?php echo $view['assets']->getAssetPrefix(true); ?>';
    var mauticContent     = '<?php $view['slots']->output('mauticContent',''); ?>';
    var mauticEnv         = '<?php echo $app->getEnvironment(); ?>';
    var mauticLang        = {
        chosenChooseOne: '<?php echo $view['translator']->trans('mautic.core.form.chooseone'); ?>',
        chosenChooseMore: '<?php echo $view['translator']->trans('mautic.core.form.choosemultiple'); ?>',
        chosenNoResults: '<?php echo $view['translator']->trans('mautic.core.form.nomatches'); ?>',
        pleaseWait: '<?php echo $view['translator']->trans('mautic.core.wait'); ?>'
    };
    <?php if ($webNotificationsEnabled) : ?>
    var OneSignal = OneSignal || [];
    OneSignal.push(["init", {
        appId: "ab44aea7-ebe8-4bf4-bb7c-aa47e22d0364",
        safari_web_id: 'web.onesignal.auto.31ba082c-c81b-42a5-be17-ec59d526e60e',
        autoRegister: true,
        subdomainName: 'dev-mautic',
        notifyButton: {
            enable: true // Set to false to hide
        }
    }]);
    OneSignal.push(function() {
        // Occurs when the user's subscription changes to a new value.
        OneSignal.on('subscriptionChange', function (isSubscribed) {
            console.log("The user's subscription state is now:", isSubscribed);
            console.log("The user's OneSignal ID is now:", OneSignal.getUserId());
        });
    });
    <?php endif; ?>
</script>
<?php $view['assets']->outputSystemScripts(); ?>
<?php $view['assets']->loadEditor(); ?>