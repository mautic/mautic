<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\AbstractClassNameMustBeAbstractRule;

/**
 * @extends RuleTestCase<AbstractClassNameMustBeAbstractRule>
 */
final class AbstractClassNameMustBeAbstractRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new AbstractClassNameMustBeAbstractRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeAbstractClass.php'], [
            [
                'Class "AbstractParser" has "Abstract" in its name but is not declared abstract. Add the "abstract" keyword or rename the class.',
                8,
            ],
        ]);
    }

    public function testSkipRealAbstractClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/RealAbstractParser.php'], []);
    }

    public function testSkipClassWithoutAbstractInName(): void
    {
        $this->analyse([__DIR__.'/Fixture/PlainParser.php'], []);
    }

    public function testSkipFinalClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/FinalParserNamedAbstract.php'], []);
    }
}
