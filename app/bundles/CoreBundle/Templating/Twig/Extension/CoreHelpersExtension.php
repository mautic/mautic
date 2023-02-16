<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Templating\Helper\DateHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The main goal of this extension is to move a lot of PHP logic that was previously
 * present in PHP templates into an extension, which can then be parsed by Twig.
 */
class CoreHelpersExtension extends AbstractExtension
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
            // Used by CoreBundle:Helper:list_filters.html.twig
            new TwigFunction('getFilterAttributes', [$this, 'getFilterAttributes'], ['is_safe' => 'all']),
            // Used by CoreBundle:Helper:pagination.html.twig
            new TwigFunction('getPaginationAction', [$this, 'getPaginationAction'], ['is_safe' => 'all']),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', fn (string $json) => json_decode($json, true)),
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

    /**
     * @param array<string,mixed> $filter
     *
     * @return array<string>
     */
    public function getFilterAttributes(string $filterName, array $filter, string $target, string $tmpl): array
    {
        $attr       = [
            'id="'.$filterName.'"',
            'name="'.$filterName.'"',
        ];
        if (!empty($filter['multiple'])) {
            $attr[] = 'multiple';
        }

        if (!empty($filter['placeholder'])) {
            $attr[] = 'data-placeholder="'.$filter['placeholder'].'"';
        } else {
            $attr[] = 'data-placeholder="'.$this->translate->trans('mautic.core.list.filter').'"';
        }

        if (!empty($filter['onchange'])) {
            $attr[] = 'onchange="'.$filter['onchange'].'"';
        } else {
            $attr[] = 'data-toggle="listfilter"';
            $attr[] = 'data-target="'.$target.'"';
        }

        $attr[] = 'data-tmpl="'.$tmpl.'"';

        if (!empty($filter['prefix-exceptions'])) {
            $attr[] = 'data-prefix-exceptions="'.implode(',', $filter['prefix-exceptions']).'"';
        }

        return $attr;
    }

    /**
     * @param array<string,mixed> $jsArguments
     */
    public function getPaginationAction(
        int $page,
        bool $active,
        string $jsCallback,
        array $jsArguments,
        ?string $baseUrl,
        string $queryString
    ): string {
        if (!$active) {
            return 'href="javascript:void(0);"';
        }

        if ($jsCallback) {
            if ($jsArguments) {
                foreach ($jsArguments as $key => $argument) {
                    if (is_array($argument)) {
                        $jsArguments[$key] = json_encode($argument);
                    } else {
                        $jsArguments[$key] = "\"{$jsArguments[$key]}\"";
                    }
                }

                return 'href="javascript:void(0);"'." onclick='".$jsCallback.'('.implode(',', $jsArguments).", $page, this);'";
            }

            return 'href="javascript:void(0);"'." onclick='".$jsCallback."($page, this);'";
        }

        return "href=\"$baseUrl/$page{$queryString}\"";
    }
}
