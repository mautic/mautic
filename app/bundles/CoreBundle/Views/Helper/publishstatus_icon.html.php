<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$query  = (!isset($query)) ? '' : $query;

// Custom toggle
if ($query) {
    parse_str($query, $queryParam);
    if (isset($queryParam['customToggle'])) {
        $accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();
        $status   =   (bool) $accessor->getValue($item, $queryParam['customToggle']);
    }
}

// continue as standard published status
if (!isset($status)) {
    $status = $item->getPublishStatus();
}
$size   = (empty($size)) ? 'fa-lg' : $size;
switch ($status) {
    case 'published':
        $icon = ' fa-toggle-on text-success';
        $text = $view['translator']->trans('mautic.core.form.published');
        break;
    case 'unpublished':
        $icon = ' fa-toggle-off text-danger';
        $text = $view['translator']->trans('mautic.core.form.unpublished');
        break;
    case 'expired':
        $icon = ' fa-clock-o text-danger';
        $text = $view['translator']->trans('mautic.core.form.expired', [
            '%date%' => $view['date']->toFull($item->getPublishDown()),
        ]);
        break;
    case 'pending':
        $icon = ' fa-clock-o text-warning';
        $text = $view['translator']->trans('mautic.core.form.pending', [
            '%date%' => $view['date']->toFull($item->getPublishUp()),
        ]);
        break;
}
switch (true) {
    case $status === true:
        $icon = ' fa-toggle-on text-success';
        $text = $view['translator']->trans('mautic.core.form.public');
        break;
    case $status === false:
        $icon = ' fa-toggle-off text-danger';
        $text = $view['translator']->trans('mautic.core.form.not.public');
        break;
}

if (!empty($disableToggle)) {
    $icon = str_replace(['success', 'danger', 'warning'], 'muted', $icon);
}

$clickAction = (isset($disableToggle) && $disableToggle === true) ? ' disabled' : ' has-click-event';
$idClass     = str_replace('.', '-', $model).'-publish-icon'.$item->getId().md5($query);
?>

<i class="fa fa-fw <?php echo $size.' '.$icon.$clickAction.' '.$idClass; ?>" data-toggle="tooltip" data-container="body" data-placement="right" data-status="<?php echo $status; ?>" title="<?php echo $text ?>"<?php if (empty($disableToggle)): ?> onclick="Mautic.togglePublishStatus(event, '.<?php echo $idClass; ?>', '<?php echo $model; ?>', <?php echo $item->getId(); ?>, '<?php echo $query; ?>', <?php echo (isset($backdrop)) ? 'true' : 'false'; ?>);"<?php endif; ?>></i>
