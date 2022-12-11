<?php

namespace Mautic\LeadBundle\Twig;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('parseBooleanList', [$this, 'parseBooleanList']),
            new TwigFilter('parseListForChoices', [$this, 'parseListForChoices'])
        ];
    }

    public function parseListForChoices($list)
    {
        return FormFieldHelper::parseListForChoices($list);
    }

    public function parseBooleanList($list)
    {
        return FormFieldHelper::parseBooleanList($list);
    }
}
