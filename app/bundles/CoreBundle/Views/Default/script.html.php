<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$mauticContent = $view['slots']->get(
    'mauticContent',
    isset($mauticTemplateVars['mauticContent']) ? $mauticTemplateVars['mauticContent'] : ''
);
?>

<script>
    var mauticBasePath    = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl     = '<?php echo $view['router']->path('mautic_base_index'); ?>';
    var mauticAjaxUrl     = '<?php echo $view['router']->path('mautic_core_ajax'); ?>';
    var mauticAssetPrefix = '<?php echo $view['assets']->getAssetPrefix(true); ?>';
    var mauticContent     = '<?php echo $mauticContent; ?>';
    var mauticEnv         = '<?php echo $app->getEnvironment(); ?>';
    var mauticLang        = <?php echo $view['translator']->getJsLang(); ?>;
</script>
<?php $view['assets']->outputSystemScripts(true); ?>
