<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

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
    public function __construct(
        private TranslatorInterface $translate,
    ) {
    }

    public function getFunctions()
    {
        return [
            // Used by CoreBundle:Helper:list_filters.html.twig
            new TwigFunction('getFilterAttributes', [$this, 'getFilterAttributes'], ['is_safe' => 'all']),
            // Used by CoreBundle:Helper:pagination.html.twig
            new TwigFunction('getPaginationAction', [$this, 'getPaginationAction'], ['is_safe' => 'all']),
            new TwigFunction('md5', fn (string $string) => md5($string), ['is_safe' => 'all']),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', fn (string $json) => json_decode($json, true)),
            new TwigFilter('parse_str', [$this, 'parseString']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parseString(string $string): array
    {
        parse_str($string, $result);

        return $result;
    }

    /**
     * @param array<string,mixed> $filter
     *
     * @return array<string, string>
     */
    public function getFilterAttributes(string $filterName, array $filter, string $target, string $tmpl): array
    {
        $attr = [
            'id'        => $filterName,
            'name'      => $filterName,
            'data-tmpl' => $tmpl,
        ];

        // Set data-placeholder
        $attr['data-placeholder'] = !empty($filter['placeholder'])
            ? $filter['placeholder']
            : $this->translate->trans('mautic.core.list.filter');

        // Handle onchange vs listfilter attributes
        if (!empty($filter['onchange'])) {
            $attr['onchange'] = $filter['onchange'];
        } else {
            $attr['data-toggle']  = 'listfilter';
            $attr['data-target']  = $target;
        }

        // Add prefix exceptions if any
        if (!empty($filter['prefix-exceptions'])) {
            $attr['data-prefix-exceptions'] = implode(',', $filter['prefix-exceptions']);
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
        string $queryString,
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
