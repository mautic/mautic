<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The main goal of this extension is to move a lot of PHP logic that was previously
 * present in PHP templates into an extension, which can then be parsed by Twig.
 */
class CoreHelpersExtension
{
    public function __construct(
        private TranslatorInterface $translate,
    ) {
    }

    public function getFunctions()
    {
        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[\Twig\Attribute\AsTwigFilter('parse_str')]
    public function parseString(string $string): array
    {
        parse_str($string, $result);

        return $result;
    }

    /**
     * @param array<string,mixed> $filter
     *
     * @return array<string>
     */
    #[\Twig\Attribute\AsTwigFunction('getFilterAttributes', isSafe: ['all'])]
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
    #[\Twig\Attribute\AsTwigFunction('getPaginationAction', isSafe: ['all'])]
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
