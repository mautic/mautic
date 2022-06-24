<?php

// Scripts and token for lookup form fields.
$jQueryPaths = [
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
    'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js',
    $view['assets']->getUrl('app/bundles/FormBundle/Assets/Form/js/lookup-autocomplete.js', null, null, true),
    $view['assets']->getUrl('app/bundles/FormBundle/Assets/Form/js/autocomplete-extension.js', null, null, true),
];

echo "<link rel='stylesheet' href='https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css'>";

foreach ($jQueryPaths as $path) {
    echo "<script src='$path'></script>";
}
echo "<script>var mauticAjaxCsrf = '".$view['security']->getCsrfToken('mautic_ajax_post')."'</script>";
