<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticCoreBundle:Exception:index.html.php');
}

$view['slots']->set('pageHeader', $status_code . ' ' . $status_text);

/** @var $exception \Symfony\Component\HttpKernel\Exception\FlattenException */
?>
<h3><?php echo $view['exception']->formatFileFromText($exception->getMessage()); ?></h3>

<div><strong><?php echo $status_code; ?></strong> <?php echo $status_text; ?> - <?php echo $view['exception']->abbrClass($exception->getClass()); ?></div>

<div class="text-center">
    <a href="<?php echo $view['router']->generate('mautic_dashboard_index'); ?>" role="button" class="btn btn-primary mt-20">
        <?php echo $view['translator']->trans('Return to Dashboard'); ?>
    </a>
</div>
