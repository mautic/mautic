<?php
// Variables
$query      = isset($query) ? $query : '';
$size       = (empty($size)) ? 'fa-lg' : $size;
$attributes = isset($attributes) ? $attributes : [];
$transKeys  = isset($transKeys) ? $transKeys : [];

// If query exists
if ($query) {
    parse_str($query, $queryParam);
    if (isset($queryParam['customToggle'])) {
        $accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();
        $status   =   (bool) $accessor->getValue($item, $queryParam['customToggle']);
    }
}

$status = isset($status) ? $status : $item->getPublishStatus();

switch ($status) {
    case 'published':
        $icon = 'fa-toggle-on text-success';
        $text = $view['translator']->trans('mautic.core.form.published');
        break;
    case 'unpublished':
        $icon = 'fa-toggle-off text-danger';
        $text = $view['translator']->trans('mautic.core.form.unpublished');
        break;
    case 'expired':
        $icon = 'fa-clock-o text-danger';
        $text = $view['translator']->trans('mautic.core.form.expired_to', ['%date%' => $view['date']->toFull($item->getPublishDown())]);
        break;
    case 'pending':
        $icon = 'fa-clock-o text-warning';
        $text = $view['translator']->trans('mautic.core.form.pending.start_at', ['%date%' => $view['date']->toFull($item->getPublishUp())]);
        break;
    case true:
        $icon = 'fa-toggle-on text-success';
        $text = $view['translator']->trans('mautic.core.form.public');
        break;
    case false:
        $icon = 'fa-toggle-off text-danger';
        $text = $view['translator']->trans('mautic.core.form.not.public');
        break;
}

$text .= isset($aditionalLabel) ? $aditionalLabel : '';

if (isset($disableToggle) && !empty($disableToggle)) {
    $icon = str_replace(['success', 'danger', 'warning'], 'muted', $icon);
}

$clickAction = (isset($disableToggle) && true === $disableToggle) ? 'disabled' : 'has-click-event';
$idClass     = str_replace('.', '-', $model).'-publish-icon'.$item->getId().md5($query);

$backdropFlag = isset($backdrop) ? 'true' : 'false';

if (!isset($onclick) || (isset($onclick) && empty($onclick))) {
    $onclick = sprintf("Mautic.togglePublishStatus(event, '.%s', '%s', '%s', '%s', %s);", $idClass, $model, $item->getId(), $query, $backdropFlag);
}

$defaultAttributes = [
    'data-container' => 'body',
    'data-placement' => 'right',
    'data-toggle'    => 'tooltip',
    'data-status'    => $status,
];

if (!empty($attributes)) {
    $attributes = array_merge($attributes, [
        'data-id-class' => '.'.$idClass,
        'data-model'    => $model,
        'data-item-id'  => $item->getId(),
        'data-query'    => $query,
        'data-backdrop' => $backdropFlag,
    ]);
}

if (isset($transKeys)) {
    foreach ($transKeys as $k => $v) {
        $attributes = array_merge($attributes, [
            $k => $view['translator']->trans($v),
        ]);
    }
}

$allDataAttrs   = array_merge($defaultAttributes, $attributes);
$dataAttributes = '';

foreach ($allDataAttrs as $k => $v) {
    $dataAttributes .= sprintf('%s="%s" ', $k, $v);
}

?>

<i class="fa fa-fw <?php echo $size; ?> <?php echo $icon; ?> <?php echo $clickAction; ?> <?php echo $idClass; ?> toggle-publish-status"
   title="<?php echo $text; ?>"
    <?php echo $dataAttributes; ?>
   <?php if (empty($disableToggle)) : ?>onclick="<?php echo $onclick; ?>"<?php endif; ?>></i>
