<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

use Psr\Log\LoggerInterface;

final class DuplicateTypeConstructor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LoggerInterface $mainLogger,
        private readonly ?LoggerInterface $debugLogger = null,
    ) {
    }
}
