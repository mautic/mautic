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
        $text = $view['translator']->trans('mautic.core.form.expired_to', [
            '%date%' => $view['date']->toFull($item->getPublishDown()),
        ]);
        break;
    case 'pending':
        $icon = ' fa-clock-o text-warning';
        $text = $view['translator']->trans('mautic.core.form.pending.start_at', [
            '%date%' => $view['date']->toFull($item->getPublishUp()),
        ]);
        break;
}
switch (true) {
    case true === $status:
        $icon = ' fa-toggle-on text-success';
        $text = $view['translator']->trans('mautic.core.form.public');
        break;
    case false === $status:
        $icon = ' fa-toggle-off text-danger';
        $text = $view['translator']->trans('mautic.core.form.not.public');
        break;
}

if (isset($aditionalLabel)) {
    $text .= $aditionalLabel;
}

if (!empty($disableToggle)) {
    $icon = str_replace(['success', 'danger', 'warning'], 'muted', $icon);
}

$clickAction = (isset($disableToggle) && true === $disableToggle) ? ' disabled' : ' has-click-event';
$idClass     = str_replace('.', '-', $model).'-publish-icon'.$item->getId().md5($query);

$backdropFlag = (isset($backdrop)) ? 'true' : 'false';

$onclick = "Mautic.togglePublishStatus(event, '.{$idClass}', '{$model}', '{$item->getId()}', '{$query}', {$backdropFlag})";

$defaultAttributes = [
    'data-container' => 'body',
    'data-placement' => 'right',
    'data-toggle'    => 'tooltip',
    'data-status'    => $status,
];

$attributes = [];

// Update the attribute for campaign.
if ('campaign' === $model) {
    $onclick    = 'Mautic.confirmationCampaignPublishStatus(mQuery(this));';
    $attributes = [
        'data-toggle'           => 'confirmation',
        'data-confirm-callback' => 'confirmCallbackCampaignPublishStatus',
        'data-cancel-callback'  => 'dismissConfirmation',
        'data-message'          => $view['translator']->trans('mautic.campaign.form.confirmation.message'),
        'data-confirm-text'     => $view['translator']->trans('mautic.campaign.form.confirmation.confirm_text'),
        'data-cancel-text'      => $view['translator']->trans('mautic.campaign.form.confirmation.cancel_text'),
        'data-id-class'         => '.'.$idClass,
        'data-model'            => $model,
        'data-item-id'          => $item->getId(),
        'data-query'            => $query,
        'data-backdrop'         => $backdropFlag,
    ];
}

$allDataAttrs = array_merge($attributes + $defaultAttributes);

$dataAttributes = implode(' ', array_map(
    function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
    $allDataAttrs,
    array_keys($allDataAttrs)
));
?>

<i class="fa fa-fw <?php echo $size.' '.$icon.$clickAction.' '.$idClass; ?> toggle-publish-status"  title="<?php echo $text; ?>" <?php echo $dataAttributes; ?> <?php if (empty($disableToggle)): ?> onclick="<?php echo $onclick; ?>"<?php endif; ?>></i>
