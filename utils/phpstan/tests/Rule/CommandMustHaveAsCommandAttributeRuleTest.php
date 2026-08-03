<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\CommandMustHaveAsCommandAttributeRule;

/**
 * @extends RuleTestCase<CommandMustHaveAsCommandAttributeRule>
 */
final class CommandMustHaveAsCommandAttributeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CommandMustHaveAsCommandAttributeRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/CommandMissingAttribute.php'], [
            [
                'Class "Utils\PHPStan\Tests\Rule\Fixture\CommandMissingAttribute" extends Command but is missing the #[AsCommand] attribute.',
                9,
            ],
        ]);
    }

    public function testSkipCommandWithAttribute(): void
    {
        $this->analyse([__DIR__.'/Fixture/CommandWithAttribute.php'], []);
    }

    public function testSkipAbstractBaseCommand(): void
    {
        $this->analyse([__DIR__.'/Fixture/AbstractBaseCommand.php'], []);
    }

    public function testSkipNonCommandClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/PlainNonCommand.php'], []);
    }

    public function testSkipAnonymousClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/AnonymousCommand.php'], []);
    }
}
