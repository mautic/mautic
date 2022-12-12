<?php

$mauticContent = $view['slots']->get(
    'mauticContent',
    isset($mauticTemplateVars['mauticContent']) ? $mauticTemplateVars['mauticContent'] : ''
);

$editorFonts = (array) $view['config']->get('editor_fonts');
usort($editorFonts, static function ($fontA, $fontB): int {
    $fontAName = $fontA['name'] ?? '';
    $fontBName = $fontB['name'] ?? '';

    return strcasecmp($fontAName, $fontBName);
});
?>

<script>
    var mauticBasePath    = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl     = '<?php echo $view['router']->path('mautic_base_index'); ?>';
    var mauticAjaxUrl     = '<?php echo $view['router']->path('mautic_core_ajax'); ?>';
    var mauticAjaxCsrf    = '<?php echo $view['security']->getCsrfToken('mautic_ajax_post'); ?>';
    var mauticImagesPath  = '<?php echo $view['assets']->getImagesPath(); ?>';
    var mauticAssetPrefix = '<?php echo $view['assets']->getAssetPrefix(true); ?>';
    var mauticContent     = '<?php echo $mauticContent; ?>';
    var mauticEnv         = '<?php echo $app->getEnvironment(); ?>';
    var mauticLang        = <?php echo $view['translator']->getJsLang(); ?>;
    var mauticLocale      = '<?php echo $app->getRequest()->getLocale(); ?>';
    var mauticEditorFonts = <?php echo json_encode($editorFonts); ?>;
</script>
<?php $view['assets']->outputSystemScripts(true); ?>
