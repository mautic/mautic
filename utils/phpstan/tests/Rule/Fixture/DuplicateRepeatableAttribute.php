<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

#[RepeatableAttribute]
#[RepeatableAttribute]
final class DuplicateRepeatableAttribute
{
    #[RepeatableAttribute]
    #[RepeatableAttribute]
    private string $name;

    #[NonRepeatableAttribute]
    public function run(): void
    {
    }
}
