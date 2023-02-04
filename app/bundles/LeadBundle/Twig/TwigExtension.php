<?php

namespace Mautic\LeadBundle\Twig;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * TwigExtension class.
 */
class TwigExtension extends AbstractExtension
{
    /**
     * getFilters function.
     *
     * @return mixed[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('parseBooleanList', [$this, 'parseBooleanList']),
            new TwigFilter('parseListForChoices', [$this, 'parseListForChoices']),
        ];
    }

    /**
     * Parse Boolean List.
     *
     * @param mixed[] $list
     *
     * @return mixed[]
     */
    public function parseListForChoices($list)
    {
        return FormFieldHelper::parseListForChoices($list);
    }

    /**
     * Parse Boolean List.
     *
     * @param mixed[] $list
     *
     * @return mixed[]
     */
    public function parseBooleanList($list)
    {
        return FormFieldHelper::parseBooleanList($list);
    }
}
