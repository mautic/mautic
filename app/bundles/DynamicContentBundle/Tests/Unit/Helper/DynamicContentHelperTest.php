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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DynamicContentHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&DynamicContentModel
     */
    private MockObject $mockModel;

    /**
     * @var MockObject&RealTimeExecutioner
     */
    private MockObject $realTimeExecutioner;

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
        $this->mockModel           = $this->createMock(DynamicContentModel::class);
        $this->realTimeExecutioner = $this->createMock(RealTimeExecutioner::class);
        $this->mockDispatcher      = $this->createMock(EventDispatcher::class);
        $this->leadModel           = $this->createMock(LeadModel::class);
        $this->helper              = new DynamicContentHelper(
            $this->mockModel,
            $this->realTimeExecutioner,
            $this->mockDispatcher,
            $this->leadModel,
        );
    }

    public function testGetDwcBySlotNameWithPublished(): void
    {
        $this->mockModel->expects($this->exactly(2))
            ->method('getEntities')
            ->withConsecutive(
                [
                    [
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
                    ],
                ],
                [
                    [
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
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(['some entity'], []);

        // Only get published
        $this->assertCount(1, $this->helper->getDwcsBySlotName('test', true));

        // Get all
        $this->assertCount(0, $this->helper->getDwcsBySlotName('secondtest'));
    }

    public function testGetDynamicContentSlotForLeadWithListenerFindingMatch(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setFields(['email' => 'ma@ka.t', 'id' => 123]);

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
        $this->mockDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(
                        function (ContactFiltersEvaluateEvent $event) use ($contact, $slot) {
                            $this->assertSame($contact, $event->getContact());
                            $this->assertSame($slot->getFilters(), $event->getFilters());

                            $event->setIsEvaluated(true);
                            $event->setIsMatched(true); // Match found in a subscriber.

                            return true;
                        }
                    ),
                    DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE,
                ],
                [
                    $this->callback(
                        function (TokenReplacementEvent $event) use ($contact, $slot) {
                            $this->assertSame($contact, $event->getLead());
                            $this->assertSame($slot->getContent(), $event->getContent());

                            return true;
                        }
                    ),
                    DynamicContentEvents::TOKEN_REPLACEMENT,
                ]
            );

        Assert::assertSame(
            '<p>test</p>',
            $this->helper->getDynamicContentSlotForLead($slotName, $contact)
        );
    }

    public function testGetDynamicContentSlotForLeadWithListenerNotFindingMatch(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setFields(['email' => 'ma@ka.t', 'id' => 123]);

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
        $this->mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(
                        function (ContactFiltersEvaluateEvent $event) use ($contact, $slot) {
                            $this->assertSame($contact, $event->getContact());
                            $this->assertSame($slot->getFilters(), $event->getFilters());

                            // Match not found in any subscriber.

                            return true;
                        }
                    ),
                    DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE,
                ]
            );

        Assert::assertSame(
            '', // No content returned as the filter did not match anything.
            $this->helper->getDynamicContentSlotForLead($slotName, $contact)
        );
    }

    public function testGetDynamicContentSlotForLeadWithNoListenerWithMatchingFilter(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setFields(['email' => 'ma@ka.t', 'id' => 123]);

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
        $this->mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(
                        function (TokenReplacementEvent $event) use ($contact, $slot) {
                            $this->assertSame($contact, $event->getLead());
                            $this->assertSame($slot->getContent(), $event->getContent());

                            return true;
                        }
                    ),
                    DynamicContentEvents::TOKEN_REPLACEMENT,
                ]
            );

        Assert::assertSame(
            '<p>test</p>',
            $this->helper->getDynamicContentSlotForLead($slotName, $contact)
        );
    }

    public function testGetDynamicContentSlotForLeadWithNoListenerWithNotMatchingFilter(): void
    {
        $slotName = 'test';
        $contact  = new Lead();
        $contact->setFields(['email' => 'ma@ka.t', 'id' => 123]);

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

        Assert::assertSame(
            '',
            $this->helper->getDynamicContentSlotForLead($slotName, $contact)
        );
    }
}
