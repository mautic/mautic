<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class PurifyExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'purify_allow_target_blank', isSafe: ['html'])]
    public function purifyAllowTargetBlank(?string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.TargetBlank', true);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
