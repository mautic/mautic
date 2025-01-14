<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Provider;

use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FieldChoicesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldChoicesProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FieldChoicesProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->provider   = new FieldChoicesProvider($this->dispatcher);
    }

    public function testGetChoicesForFieldThatDoesNotHaveAnyChoices(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE,
                $this->callback($this->setSomeChoicesLikeASubscriber())
            );

        $this->expectException(ChoicesNotFoundException::class);
        $this->provider->getChoicesForField('text', 'email');
    }

    public function testGetChoicesForFieldThatHasTypeChoices(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE,
                $this->callback($this->setSomeChoicesLikeASubscriber())
            );

        // Calling it twice to ensure the cache is working and the event is triggered only once.
        $this->provider->getChoicesForField('country', 'country_field_a');
        $choices = $this->provider->getChoicesForField('country', 'country_field_a');

        $this->assertSame(['Czech Republic' => 'Czech Republic'], $choices);
    }

    public function testGetChoicesForFieldThatHasAliasChoices(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE,
                $this->callback($this->setSomeChoicesLikeASubscriber())
            );

        // Calling it twice to ensure the cache is working and the event is triggered only once.
        $this->provider->getChoicesForField('select', 'select_a');
        $choices = $this->provider->getChoicesForField('select', 'select_a');

        $this->assertSame(['Choice A' => 'choice_a'], $choices);
    }

    private function setSomeChoicesLikeASubscriber(): callable
    {
        return function (ListFieldChoicesEvent $event) {
            $event->setChoicesForFieldAlias(
                'select_a',
                ['Choice A' => 'choice_a']
            );

            $event->setChoicesForFieldType(
                'country',
                ['Czech Republic' => 'Czech Republic']
            );

            return true;
        };
    }
}
