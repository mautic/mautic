<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$locale = $app->getRequest()->getLocale();
$js = <<<js
<script src="//platform.linkedin.com/in.js" type="text/javascript">
    lang: $locale
</script>
js;
$view['slots']->addCustomDeclaration($js, 'bodyClose');

$counter     = (!empty($settings['counter'])) ? $settings['counter'] : 'none';
$dataCounter = ($counter != 'none') ? ' data-counter="'.$settings['counter'].'"' : '';
?>
<div class="share-button linkedin-share-button layout-<?php echo $counter; ?>">
<script type="IN/Share"<?php echo $dataCounter; ?>></script>
</div>
