<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Ai;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class AiTextResult
{
    public function __construct(
        #[\SensitiveParameter]
        public string $text,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $totalTokens = null,
    ) {
    }
}
