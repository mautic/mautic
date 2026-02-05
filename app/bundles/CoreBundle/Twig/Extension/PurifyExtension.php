<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class PurifyExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('purify_allow_target_blank', [$this, 'purifyAllowTargetBlank'], ['is_safe' => ['html']]),
        ];
    }

    public function purifyAllowTargetBlank(?string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.TargetBlank', true);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
