<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<script>
    var mauticBasePath = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl  = '<?php echo $view['router']->generate("mautic_dashboard_index"); ?>';
    var mauticAjaxUrl  = '<?php echo $view['router']->generate("mautic_core_ajax"); ?>';
    var mauticContent  = '<?php $view['slots']->output('mauticContent',''); ?>';
    var mauticEnv      = '<?php echo $app->getEnvironment(); ?>';
</script>
<?php $view['assets']->outputSystemScripts(); ?>
<?php //load file ?>
<script>
    <?php if ($app->getEnvironment() === "dev"): ?>
    mQuery( document ).ajaxComplete(function(event, XMLHttpRequest, ajaxOption){
        if(XMLHttpRequest.responseJSON && typeof XMLHttpRequest.responseJSON.ignore_wdt == 'undefined' && XMLHttpRequest.getResponseHeader('x-debug-token')) {
            MauticVars.showLoadingBar = false;
            mQuery('.sf-toolbar-block').remove();
            mQuery('.sf-minitoolbar').remove();
            mQuery('.sf-toolbarreset').remove();
            mQuery('.sf-toolbar').remove();
            mQuery("[id^=sfToolbarClearer]").remove();
            mQuery.get(mauticBaseUrl + '_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'),function(data){
                mQuery('body').append(data);
            });
        }
    });
    <?php endif; ?>
</script>

<?php $view['assets']->loadEditor(); ?>
<?php $view['assets']->outputScripts("bodyClose"); ?>
<script>
    Mautic.onPageLoad();
</script>