<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Unit\Helper;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class DynamicContentHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&DynamicContentModel
     */
    private MockObject $mockModel;

    /**
     * @var MockObject&EventDispatcher
     */
    private MockObject $mockDispatcher;

    /**
     * @var MockObject&LeadModel
     */
    private MockObject $leadModel;

    private DynamicContentHelper $helper;

    protected function setUp(): void
    {
        $this->mockModel            = $this->createMock(DynamicContentModel::class);
        $this->mockDispatcher       = $this->createMock(EventDispatcher::class);
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->helper               = new DynamicContentHelper(
            $this->mockModel,
            $this->createStub(RealTimeExecutioner::class),
            $this->mockDispatcher,
            $this->leadModel,
            $this->createStub(LeadListRepository::class),
            $this->createStub(CompanyLeadRepository::class),
            $this->createStub(TagRepository::class),
        );
    }

    public function testGetDwcBySlotNameWithPublished(): void
    {
        $matcher = $this->exactly(2);
        $this->mockModel->expects($matcher)
            ->method('getEntities')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame([
                        'filter' => [
                            'where' => [
                                [
                                    'col'  => 'e.slotName',
                                    'expr' => 'eq',
                                    'val'  => 'test',
                                ],
                                [
                                    'col'  => 'e.isPublished',
                                    'expr' => 'eq',
                                    'val'  => 1,
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                        'orderBy'          => 'e.displayOrder',
                    ], $parameters[0]);

                    return ['some entity'];
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame([
                        'filter' => [
                            'where' => [
                                [
                                    'col'  => 'e.slotName',
                                    'expr' => 'eq',
                                    'val'  => 'secondtest',
                                ],
                            ],
                        ],
                        'ignore_paginator' => true,
                        'orderBy'          => 'e.displayOrder',
                    ], $parameters[0]);

                    return [];
                }
            });

        // Only get published
        $this->assertCount(1, $this->helper->getDwcsBySlotName('test', true));

        // Get all
        $this->assertCount(0, $this->helper->getDwcsBySlotName('secondtest'));
    }

    public function testGetDynamicContentSlotForLeadWithListenerFindingMatch(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setId(123);
        $contact->setFields(['email' => 'ma@ka.t']);

        $slot = new DynamicContent();
        $slot->setName($slotName);
        $slot->setIsCampaignBased(false);
        // Setting filter that is not known to Mautic, but is for a plugin.
        $slot->setFilters([['field' => 'unicorn', 'type' => 'text', 'operator' => '=', 'filter' => 'magic']]);
        $slot->setContent('<p>test</p>');

        $this->mockModel->method('getEntities')
            ->willReturn([$slot]);

        $this->mockModel->method('getTranslatedEntity')
            ->willReturn([$slot, $slot]);

        $this->leadModel->method('getEntity')
            ->with(123)
            ->willReturn($contact);

        $this->mockDispatcher->method('hasListeners')->willReturn(true);
        $matcher = $this->exactly(2);
        $this->mockDispatcher->expects($matcher)
            ->method('dispatch')->willReturnCallback(function (...$parameters) use ($matcher, $contact, $slot): object {
                if (1 === $matcher->numberOfInvocations()) {
                    $callback = function (ContactFiltersEvaluateEvent $event) use ($contact, $slot): void {
                        $this->assertSame($contact, $event->getContact());
                        $this->assertSame($slot->getFilters(), $event->getFilters());

                        $event->setIsEvaluated(true);
                        $event->setIsMatched(true); // Match found in a subscriber.
                    };
                    $callback($parameters[0]);
                    $this->assertSame(DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $callback = function (TokenReplacementEvent $event) use ($contact, $slot): void {
                        $this->assertSame($contact, $event->getLead());
                        $this->assertSame($slot->getContent(), $event->getContent());
                    };
                    $callback($parameters[0]);
                    $this->assertSame(DynamicContentEvents::TOKEN_REPLACEMENT, $parameters[1]);
                }

                return $parameters[0];
            });

        $this->assertSame('<p>test</p>', $this->helper->getDynamicContentSlotForLead($slotName, $contact));
    }

    public function testGetDynamicContentSlotForLeadWithListenerNotFindingMatch(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setId(123);
        $contact->setFields(['email' => 'ma@ka.t']);

        $slot = new DynamicContent();
        $slot->setName($slotName);
        $slot->setIsCampaignBased(false);
        // Setting filter that is not known to Mautic, nor any plugin.
        $slot->setFilters([['field' => 'unicorn', 'type' => 'text', 'operator' => '=', 'filter' => 'magic']]);
        $slot->setContent('<p>test</p>');

        $this->mockModel->method('getEntities')
            ->willReturn([$slot]);

        $this->mockModel->method('getTranslatedEntity')
            ->willReturn([$slot, $slot]);

        $this->leadModel->method('getEntity')
            ->with(123)
            ->willReturn($contact);

        $this->mockDispatcher->method('hasListeners')->willReturn(true);
        $matcher = $this->once();
        $this->mockDispatcher->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(
                function (...$parameters) use ($matcher, $contact, $slot): object {
                    if (1 === $matcher->numberOfInvocations()) {
                        $callback = function (ContactFiltersEvaluateEvent $event) use ($contact, $slot): void {
                            $this->assertSame($contact, $event->getContact());
                            $this->assertSame($slot->getFilters(), $event->getFilters());

                            // Match not found in any subscriber.
                        };
                        $callback($parameters[0]);
                        $this->assertSame(DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE, $parameters[1]);
                    }

                    return $parameters[0];
                }
            );

        $this->assertSame(
            '',
            // No content returned as the filter did not match anything.
            $this->helper->getDynamicContentSlotForLead($slotName, $contact)
        );
    }

    public function testGetDynamicContentSlotForLeadWithNoListenerWithMatchingFilter(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setId(123);
        $contact->setFields(['email' => 'ma@ka.t']);

        $slot = new DynamicContent();
        $slot->setName($slotName);
        $slot->setIsCampaignBased(false);
        $slot->setFilters([['field' => 'email', 'type' => 'email', 'operator' => '=', 'filter' => 'ma@ka.t']]);
        $slot->setContent('<p>test</p>');

        $this->mockModel->method('getEntities')
            ->willReturn([$slot]);

        $this->mockModel->method('getTranslatedEntity')
            ->willReturn([$slot, $slot]);

        $this->leadModel->method('getEntity')
            ->with(123)
            ->willReturn($contact);

        $this->mockDispatcher->method('hasListeners')->willReturn(false);
        $matcher = $this->once();
        $this->mockDispatcher->expects($matcher)
            ->method('dispatch')
            ->willReturnCallback(
                function (...$parameters) use ($matcher, $contact, $slot): object {
                    if (1 === $matcher->numberOfInvocations()) {
                        $callback = function (TokenReplacementEvent $event) use ($contact, $slot): void {
                            $this->assertSame($contact, $event->getLead());
                            $this->assertSame($slot->getContent(), $event->getContent());
                        };
                        $callback($parameters[0]);
                        $this->assertSame(DynamicContentEvents::TOKEN_REPLACEMENT, $parameters[1]);
                    }

                    return $parameters[0];
                }
            );

        $this->assertSame('<p>test</p>', $this->helper->getDynamicContentSlotForLead($slotName, $contact));
    }

    public function testGetDynamicContentSlotForLeadWithNoListenerWithNotMatchingFilter(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setId(123);
        $contact->setFields(['email' => 'ma@ka.t']);

        $slot = new DynamicContent();
        $slot->setName($slotName);
        $slot->setIsCampaignBased(false);
        $slot->setFilters([['field' => 'email', 'type' => 'email', 'operator' => '=', 'filter' => 'uni@co.rn']]);
        $slot->setContent('<p>test</p>');

        $this->mockModel->method('getEntities')
            ->willReturn([$slot]);

        $this->mockModel->method('getTranslatedEntity')
            ->willReturn([$slot, $slot]);

        $this->leadModel->method('getEntity')
            ->with(123)
            ->willReturn($contact);

        $this->mockDispatcher->method('hasListeners')->willReturn(false);
        $this->mockDispatcher->expects($this->never())->method('dispatch');

        $this->assertSame('', $this->helper->getDynamicContentSlotForLead($slotName, $contact));
    }

    /**
     * @dataProvider replaceDWCTokenToHtmlTagDataProvider
     */
    public function testReplaceDWCTokenToHtmlTag(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->helper->replaceDWCTokenToHtmlTag($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function replaceDWCTokenToHtmlTagDataProvider(): iterable
    {
        yield 'simple token without closing tag' => [
            '{dwc=my-slot}',
            '<div data-slot="dwc" data-param-slot-name="my-slot"></div>',
        ];

        yield 'token with default content and closing tag preserves default content' => [
            '{dwc=my-slot}Default content goes here{/dwc}',
            '<div data-slot="dwc" data-param-slot-name="my-slot">Default content goes here</div>',
        ];

        yield 'token with empty default content and closing tag' => [
            '{dwc=my-slot}{/dwc}',
            '<div data-slot="dwc" data-param-slot-name="my-slot"></div>',
        ];

        yield 'multiple tokens with closing tags preserve default content' => [
            '<p>{dwc=slot1}Default 1{/dwc}</p><p>{dwc=slot2}Default 2{/dwc}</p>',
            '<p><div data-slot="dwc" data-param-slot-name="slot1">Default 1</div></p><p><div data-slot="dwc" data-param-slot-name="slot2">Default 2</div></p>',
        ];

        yield 'mixed tokens - with and without closing tags' => [
            '<p>{dwc=slot1}Default{/dwc}</p><p>{dwc=slot2}</p>',
            '<p><div data-slot="dwc" data-param-slot-name="slot1">Default</div></p><p><div data-slot="dwc" data-param-slot-name="slot2"></div></p>',
        ];

        yield 'token with HTML in default content preserves HTML' => [
            '{dwc=my-slot}<strong>Bold default</strong>{/dwc}',
            '<div data-slot="dwc" data-param-slot-name="my-slot"><strong>Bold default</strong></div>',
        ];

        yield 'token with multiline default content' => [
            "{dwc=my-slot}Line 1\nLine 2{/dwc}",
            "<div data-slot=\"dwc\" data-param-slot-name=\"my-slot\">Line 1\nLine 2</div>",
        ];
    }
}
