<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\EntityEventListenerMustCheckInstanceofRule;

/**
 * @extends RuleTestCase<EntityEventListenerMustCheckInstanceofRule>
 */
final class EntityEventListenerMustCheckInstanceofRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new EntityEventListenerMustCheckInstanceofRule();
    }

    public function testMissingInstanceof(): void
    {
        $this->analyse([__DIR__.'/Fixture/EntityEventInstanceof/MissingInstanceofSubscriber.php'], [
            [
                'Method "onPostSave()" listens to generic "Mautic\CoreBundle\Event\EntityPostSave" event that fires for every entity. Check "$event->getEntity()" with instanceof first.',
                12,
            ],
            [
                'Method "onPreDelete()" listens to generic "Mautic\CoreBundle\Event\EntityPreDelete" event that fires for every entity. Check "$event->getEntity()" with instanceof first.',
                19,
            ],
        ]);
    }

    public function testSkipInstanceofOnEntityVariable(): void
    {
        $this->analyse([__DIR__.'/Fixture/EntityEventInstanceof/InstanceofVariableSubscriber.php'], []);
    }

    public function testSkipInstanceofOnGetEntityCall(): void
    {
        $this->analyse([__DIR__.'/Fixture/EntityEventInstanceof/InstanceofCallSubscriber.php'], []);
    }
}
