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
    var mauticBaseUrl = '<?php echo $view['router']->generate("mautic_core_index"); ?>';
    var mauticAjaxUrl = '<?php echo $view['router']->generate("mautic_core_ajax"); ?>';
    var mauticContent = '<?php $view['slots']->output('mauticContent',''); ?>';
</script>
<?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
<script src="<?php echo $view->escape($url) ?>"></script>
<?php endforeach; ?>
<script>
    Mautic.onPageLoad();
    <?php $view['slots']->output("jsDeclarations"); ?>
    <?php if ($app->getEnvironment() === "dev"): ?>
    mQuery( document ).ajaxComplete(function(event, XMLHttpRequest, ajaxOption){
        if(XMLHttpRequest.getResponseHeader('x-debug-token')) {
            MauticVars.showLoadingBar = false;
            mQuery('.sf-toolbar-block').remove();
            mQuery('.sf-minitoolbar').remove();
            mQuery('.sf-toolbarreset').remove();
            mQuery('.sf-toolbar').remove();
            mQuery.get(mauticBaseUrl + '_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'),function(data){
                mQuery('body').append(data);
            });
        }
    });
    <?php endif; ?>
</script>