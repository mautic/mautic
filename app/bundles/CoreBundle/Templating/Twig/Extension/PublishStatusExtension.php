<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PublishStatusExtension extends AbstractExtension
{
    private TranslatorInterface $translate;
    private DateHelper $dateHelper;

    public function __construct(TranslatorInterface $translate, DateHelper $dateHelper)
    {
        $this->translate  = $translate;
        $this->dateHelper = $dateHelper;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('publishStatusIcon', [$this, 'getPublishStatusIcon']),
        ];
    }

    /**
     * @param array<string,string> $transKeys
     * @param array<string,string> $attributes
     *
     * @return array<string,string>
     */
    public function getPublishStatusIcon(
        string $query,
        FormEntity $item,
        string $size,
        string $model,
        string $backdropFlag,
        ?string $onclick = null,
        array $transKeys = [],
        array $attributes = [],
        ?string $aditionalLabel = null,
        bool $disableToggle = false
    ): array {
        /** @var string|bool */
        $status = $item->getPublishStatus();

        if (!method_exists($item, 'getId')) {
            throw new \Exception('Cannot get publish status for entity without an ID.');
        }

        // Custom toggle
        if (!empty($query)) {
            parse_str($query, $queryParam);
            if (isset($queryParam['customToggle'])) {
                $accessor = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor();
                $status   = (bool) $accessor->getValue($item, $queryParam['customToggle']);
            }
        }

        $size   = (empty($size)) ? 'fa-lg' : $size;
        switch ($status) {
            case 'published':
                $icon = ' fa-toggle-on text-success';
                $text = $this->translate->trans('mautic.core.form.published');
                break;
            case 'unpublished':
                $icon = ' fa-toggle-off text-danger';
                $text = $this->translate->trans('mautic.core.form.unpublished');
                break;
            case 'expired':
                $icon = ' fa-clock-o text-danger';
                $text = $this->translate->trans('mautic.core.form.expired_to', [
                    '%date%' => method_exists($item, 'getPublishDown') ? $this->dateHelper->toFull($item->getPublishDown()) : '',
                ]);
                break;
            case 'pending':
                $icon = ' fa-clock-o text-warning';
                $text = $this->translate->trans('mautic.core.form.pending.start_at', [
                    '%date%' => method_exists($item, 'getPublishUp') ? $this->dateHelper->toFull($item->getPublishUp()) : '',
                ]);
                break;
        }
        switch (true) {
            case true === $status:
                $icon = ' fa-toggle-on text-success';
                $text = $this->translate->trans('mautic.core.form.public');
                break;
            case false === $status:
                $icon = ' fa-toggle-off text-danger';
                $text = $this->translate->trans('mautic.core.form.not.public');
                break;
        }

        if (!empty($aditionalLabel)) {
            $text .= $aditionalLabel;
        }

        if (true === $disableToggle) {
            $icon = str_replace(['success', 'danger', 'warning'], 'muted', $icon);
        }

        $clickAction = true === $disableToggle ? ' disabled' : ' has-click-event';
        $idClass     = str_replace('.', '-', $model).'-publish-icon'.$item->getId().md5($query);

        $onclick = $onclick ?? "Mautic.togglePublishStatus(event, '.{$idClass}', '{$model}', '{$item->getId()}', '{$query}', {$backdropFlag})";

        $defaultAttributes = [
            'data-container' => 'body',
            'data-placement' => 'right',
            'data-toggle'    => 'tooltip',
            'data-status'    => $status,
        ];

        if (!empty($attributes)) {
            $attributes['data-id-class']    = '.'.$idClass;
            $attributes['data-model']       = $model;
            $attributes['data-item-id']     = $item->getId();
            $attributes['data-query']       = $query;
            $attributes['data-backdrop']    = $backdropFlag;
        }

        if (!empty($transKeys)) {
            foreach ($transKeys as $k => $v) {
                $attributes[$k] = $this->translate->trans($v);
            }
        }

        $allDataAttrs = array_merge($attributes + $defaultAttributes);

        $dataAttributes = implode(' ', array_map(
            function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
            $allDataAttrs,
            array_keys($allDataAttrs)
        ));

        return [
            'size'           => $size,
            'icon'           => $icon,
            'clickAction'    => $clickAction,
            'idClass'        => $idClass,
            'text'           => $text,
            'dataAttributes' => $dataAttributes,
            'onclick'        => $onclick,
        ];
    }
}
