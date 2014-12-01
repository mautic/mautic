<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Default:error.html.php');
}

$view['slots']->set('pageHeader', $status_code . ' ' . $status_text);
?>
<h3>Oops! An Error Occurred</h3>
<div>It seems there was an error while processing the request.  Sorry about that.</div>
<a href="<?php echo $view['router']->generate('mautic_dashboard_index'); ?>" role="button" class="btn btn-primary pull-right mt-20">
    <?php echo $view['translator']->trans('Return to Dashboard'); ?>
</a>
