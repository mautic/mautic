<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class PurifyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('purify', $this->purify(...), ['is_safe' => ['html']]),
            new TwigFilter('purify_allow_target_blank', $this->purifyAllowTargetBlank(...), ['is_safe' => ['html']]),
            new TwigFilter('purify_builder_preview', $this->purifyBuilderPreview(...), ['is_safe' => ['html']]),
        ];
    }

    public function purify(?string $html): string
    {
        return $this->createPurifier()->purify($html ?? '');
    }

    public function purifyAllowTargetBlank(?string $html): string
    {
        return $this->createPurifier(true)->purify($html ?? '');
    }

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
