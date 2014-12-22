<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php switch ($entity->getPublishStatus()) {
    case 'published':
        $labelColor = "success";
        break;
    case 'unpublished':
    case 'expired'    :
        $labelColor = "danger";
        break;
    case 'pending':
        $labelColor = "warning";
        break;
} ?>
<?php $labelText = strtoupper($view['translator']->trans('mautic.core.form.' . $entity->getPublishStatus())); ?>
<h4 class="fw-sb"><span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span></h4>