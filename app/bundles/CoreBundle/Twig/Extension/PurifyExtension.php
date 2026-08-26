<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Attribute\AsTwigFilter;

final class PurifyExtension
{
    #[AsTwigFilter(name: 'purify_allow_target_blank', isSafe: ['html'])]
    public function purifyAllowTargetBlank(?string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.TargetBlank', true);
        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
