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
    var mauticBasePath = '<?php echo $app->getRequest()->getBasePath(); ?>';
    var mauticBaseUrl  = '<?php echo $view['router']->generate("mautic_dashboard_index"); ?>';
    var mauticAjaxUrl  = '<?php echo $view['router']->generate("mautic_core_ajax"); ?>';
    var mauticContent  = '<?php $view['slots']->output('mauticContent',''); ?>';
    var mauticEnv      = '<?php echo $app->getEnvironment(); ?>';
</script>
<?php $view['assets']->outputSystemScripts(); ?>
<?php $view['assets']->loadEditor(); ?>