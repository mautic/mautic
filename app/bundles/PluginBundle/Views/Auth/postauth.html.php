<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');
$view['slots']->set('mauticContent', 'social');

if (empty($message)):
$data = json_encode($data);
$js   = <<<JS
function postFormHandler() {
    var opener = window.opener;
    if (opener && typeof opener.postAuthCallback == 'function') {
        opener.postAuthCallback({$data});
    } else {
        Mautic.refreshIntegrationForm();
    }
    window.close()

}
(function() {
   postFormHandler();
})();
JS;
?>
<script>
    <?php echo $js; ?>
</script>
<?php else: ?>
    <div class="alert alert-<?php echo $alert; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-sm-12 text-center">
        <a class="btn btn-lg btn-primary" href="javascript:void(0);" onclick="postFormHandler();">
            <?php echo $view['translator']->trans('mautic.integration.closewindow'); ?>
        </a>
    </div>
</div>