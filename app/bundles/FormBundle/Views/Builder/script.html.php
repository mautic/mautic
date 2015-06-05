<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$fields   = $form->getFields();
?>

<script type="text/javascript">

    /** This section is only needed once per page if manually copying **/
    if (typeof MauticSDKLoaded == 'undefined') {
        var MauticSDKLoaded = true;
        var head            = document.getElementsByTagName('head')[0];
        var script          = document.createElement('script');
        script.type         = 'text/javascript';
        script.src          = '<?php echo $view['assets']->getUrl('media/js/' . ($app->getEnvironment()  == 'dev' ? 'mautic-form-src.js' : 'mautic-form.js'), null, null, true); ?>';
        script.onload       = function() {
            MauticSDK.onLoad();
        };
        head.appendChild(script);
        var MauticDomain = '<?php echo $view['assets']->getBaseUrl(); ?>';
        var MauticLang   = {
            'submittingMessage': "<?php echo $view['translator']->trans('mautic.form.submission.pleasewait'); ?>"
        }
        var MauticFormValidations  = {};
    }

    /** This is needed for each form **/
    MauticFormValidations.<?php echo $formName; ?> = {
<?php
foreach($fields as $f):
if (!$f->isRequired()) continue;
$type       = $f->getType();
$properties = $f->getProperties();
$name       = $f->getAlias();
if ((in_array($type, array('select', 'country')) && !empty($properties['multiple'])) || $type == 'checkboxgrp')
    $name .= '[]';
?>
        '<?php echo $f->getAlias(); ?>': {
            type: '<?php echo $f->getType(); ?>',
            name: '<?php echo $name; ?>'
        },
<?php endforeach; ?>
    };
</script>