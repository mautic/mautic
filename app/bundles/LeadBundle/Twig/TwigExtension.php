<?php

namespace Mautic\LeadBundle\Twig;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * TwigExtension class
 */
class TwigExtension extends AbstractExtension
{
    /**
     * getFilters function
     *
     * @return mixed[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('parseBooleanList', [$this, 'parseBooleanList']),
            new TwigFilter('parseListForChoices', [$this, 'parseListForChoices']),
            new TwigFilter('getTotal', [$this, 'getTotal']),
            new TwigFilter('unset', [$this, 'unset']),
        ];
    }

    /**
     * Parse Boolean List
     *
     * @param mixed[] $list
     * @return mixed[]
     */
    public function parseListForChoices($list)
    {
        return FormFieldHelper::parseListForChoices($list);
    }

    /**
     * Parse Boolean List
     *
     * @param mixed[] $list
     * @return mixed[]
     */
    public function parseBooleanList($list)
    {
        return FormFieldHelper::parseBooleanList($list);
    }

    public function unset($array, $key)
    {
        dd($array);
    }

    public function getTotal($a, $f, $t, $allrows, $ac)
    {
        switch ($f) {
            case 'COUNT':
            case 'SUM':
                return (int) $t + (int) $a;
            case 'AVG':
                return ($ac == $allrows) ? round(((int) $t + (int) $a) / (int) $allrows, 2) : (int) $t + (int) $a;
            case 'MAX':
                return ((int) $a >= (int) $t) ? (int) $a : (int) $t;
            case 'MIN':
                return ((int) $a <= (int) $t) ? (int) $a : (int) $t;
            default:
                return (int) $t;
        }
    }
}
