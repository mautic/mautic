<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$status = $item->getPublishStatus();
switch ($status) {
    case 'published':
        $icon = " fa-check-circle-o text-success";
        $text = $view['translator']->trans('mautic.core.form.published');
        break;
    case 'unpublished':
        $icon = " fa-times-circle-o text-danger";
        $text = $view['translator']->trans('mautic.core.form.unpublished');
        break;
    case 'expired':
        $icon = " fa-clock-o text-danger";
        $text = $view['translator']->trans('mautic.core.form.expired', array(
            '%date%' => $item->getPublishDown()->format($dateFormat)
        ));
        break;
    case 'pending':
        $icon = " fa-clock-o text-warning";
        $text = $view['translator']->trans('mautic.core.form.pending', array(
            '%date%' => $item->getPublishUp()->format($dateFormat)
        ));
        break;
}
?>

<i class="fa fa-fw fa-lg <?php echo $icon; ?> publish-icon<?php echo $item->getId(); ?>"
   data-toggle="tooltip"
   data-container="body"
   data-placement="right"
   data-status="<?php echo $status; ?>"
   data-original-title="<?php echo $text ?>"
   onclick="Mautic.togglePublishStatus(this, '<?php echo $model; ?>', <?php echo $item->getId(); ?>);"></i>