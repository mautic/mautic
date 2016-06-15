<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<script>
    var mauticBasePath    = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl     = '<?php echo $view['router']->path("mautic_base_index"); ?>';
    var mauticAjaxUrl     = '<?php echo $view['router']->path("mautic_core_ajax"); ?>';
    var mauticAssetPrefix = '<?php echo $view['assets']->getAssetPrefix(true); ?>';
    var mauticContent     = '<?php $view['slots']->output('mauticContent',''); ?>';
    var mauticEnv         = '<?php echo $app->getEnvironment(); ?>';
    var mauticLang        = {
        chosenChooseOne: '<?php echo $view['translator']->trans('mautic.core.form.chooseone'); ?>',
        chosenChooseMore: '<?php echo $view['translator']->trans('mautic.core.form.choosemultiple'); ?>',
        chosenNoResults: '<?php echo $view['translator']->trans('mautic.core.form.nomatches'); ?>',
        pleaseWait: '<?php echo $view['translator']->trans('mautic.core.wait'); ?>'
    };
</script>
<?php $view['assets']->outputSystemScripts(true); ?>
