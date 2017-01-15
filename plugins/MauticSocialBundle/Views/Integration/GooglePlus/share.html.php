<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$dataHeight     = (!empty($settings['height'])) ? ' data-height="'.$settings['height'].'"' : '';
$dataAnnotation = (!empty($settings['annotation'])) ? $settings['annotation'] : 'inline';
$locale         = $app->getRequest()->getLocale();
$language       = ($locale != 'en_US') ? "window.___gcfg = {lang: '$locale'};" : '';
$js             = <<<JS
$language
(function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/platform.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();
JS;
?>
<div class="g-plus googleplus-share-button share-button layout-<?php echo $dataAnnotation; ?>" data-action="share" data-annotation="<?php echo $dataAnnotation ?>"<?php echo $dataHeight; ?>></div>
<script><?php echo $js; ?></script>
