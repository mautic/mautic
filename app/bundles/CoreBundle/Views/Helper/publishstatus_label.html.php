<?php switch ($entity->getPublishStatus()) {
    case 'published':
        $labelColor = 'success';
        break;
    case 'unpublished':
    case 'expired':
        $labelColor = 'danger';
        break;
    case 'pending':
        $labelColor = 'warning';
        break;
} ?>
<?php $labelText = $view['translator']->trans('mautic.core.form.'.$entity->getPublishStatus()); ?>
<span class="label label-<?php echo $labelColor; ?>"><?php echo $labelText; ?></span>
