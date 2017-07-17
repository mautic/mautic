<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$scriptSrc = $view['assets']->getUrl('media/js/'.($app->getEnvironment() == 'dev' ? 'mautic-form-src.js' : 'mautic-form.js'), null, null, true);
$scriptSrc = str_replace('/index_dev.php', '', $scriptSrc);
?>

<script type="text/javascript">

    /** This section is only needed once per page if manually copying **/
    if (typeof MauticSDKLoaded == 'undefined') {
        var MauticSDKLoaded = true;
        var head            = document.getElementsByTagName('head')[0];
        var script          = document.createElement('script');
        script.type         = 'text/javascript';
        script.src          = '<?php echo $scriptSrc; ?>';
        script.onload       = function() {
            MauticSDK.onLoad();
        };
        head.appendChild(script);
        var MauticDomain = '<?php echo str_replace('/index_dev.php', '', $view['assets']->getBaseUrl()); ?>';
        var MauticLang   = {
            'submittingMessage': "<?php echo $view['translator']->trans('mautic.form.submission.pleasewait'); ?>"
        }
    }
</script>
