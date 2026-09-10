<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

#[NonRepeatableAttribute]
#[NonRepeatableAttribute]
final class DuplicateNonRepeatableAttribute
{
    #[NonRepeatableAttribute]
    #[NonRepeatableAttribute]
    private string $name;

    #[NonRepeatableAttribute]
    #[NonRepeatableAttribute]
    public function run(): void
    {
    }
}
