<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Attribute\AsTwigFilter;

final class PurifyExtension
{
    #[AsTwigFilter(name: 'purify_allow_target_blank', isSafe: ['html'])]
    public function purifyAllowTargetBlank(?string $html): string
    {
        return $this->createPurifier(true)->purify($html ?? '');
    }

    #[AsTwigFilter(name: 'purify_builder_preview', isSafe: ['html'])]
    public function purifyBuilderPreview(?string $html): string
    {
        return $this->createPurifier(false, ['form', 'script'])->purify($html ?? '');
    }

    /**
     * @param list<string> $forbiddenElements
     */
    private function createPurifier(bool $targetBlank = false, array $forbiddenElements = []): \HTMLPurifier
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.TargetBlank', $targetBlank);

        if ([] !== $forbiddenElements) {
            $config->set('HTML.ForbiddenElements', $forbiddenElements);
        }

        return new \HTMLPurifier($config);
    }
}
