<?php

declare(strict_types=1);

namespace MauticPhpStan\Tests\Rule\Fixture;

// "Abstract" in name but final - cannot be abstract, so the rule must skip it (e.g. a test class)
final class AbstractParserTest
{
    public function testParse(): void
    {
    }
}
