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
    var mauticContent = '<?php $view['slots']->output('mauticContent',''); ?>';
</script>
<?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
<script src="<?php echo $view->escape($url) ?>"></script>
<?php endforeach; ?>
<script src="<?php echo $view['assets']->getUrl('media/tinymce/tinymce.min.js'); ?>"></script>
<script src="<?php echo $view['assets']->getUrl('media/tinymce/jquery.tinymce.min.js'); ?>"></script>
<script>
    Mautic.onPageLoad();
    <?php $view['slots']->output("jsDeclarations"); ?>
    <?php if ($app->getEnvironment() === "dev"): ?>
    $( document ).ajaxComplete(function(event, XMLHttpRequest, ajaxOption){
        if(XMLHttpRequest.getResponseHeader('x-debug-token')) {
            MauticVars.showLoadingBar = false;
            $('.sf-toolbarreset').remove();
            $.get(mauticBaseUrl +'_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'),function(data){
                $('body').append(data);
            });
        }
    });
    <?php endif; ?>
</script>