<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\EventListener\FilterOperatorSubscriber;
use Mautic\LeadBundle\Exception\ChoicesNotFoundException;
use Mautic\LeadBundle\Provider\FieldChoicesProviderInterface;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

final class FilterOperatorSubscriberTest extends TestCase
{
    /**
     * @var OperatorOptions
     */
    private $operatorOptions;

    /**
     * @var MockObject|LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var MockObject|TypeOperatorProviderInterface
     */
    private $typeOperatorProvider;

    /**
     * @var MockObject|FieldChoicesProviderInterface
     */
    private $fieldChoicesProvider;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var FilterOperatorSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatorOptions      = new OperatorOptions();
        $this->leadFieldRepository  = $this->createMock(LeadFieldRepository::class);
        $this->typeOperatorProvider = $this->createMock(TypeOperatorProviderInterface::class);
        $this->fieldChoicesProvider = $this->createMock(FieldChoicesProviderInterface::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);

        $this->subscriber = new FilterOperatorSubscriber(
            $this->operatorOptions,
            $this->leadFieldRepository,
            $this->typeOperatorProvider,
            $this->fieldChoicesProvider,
            $this->translator
        );
    }

    public function testOnListOperatorsGenerate(): void
    {
        $event = new LeadListFiltersOperatorsEvent([], $this->translator);

        $this->subscriber->onListOperatorsGenerate($event);

        $operators = $event->getOperators();

        // Test that random operators exist:
        $this->assertSame(
            [
                'label'       => 'mautic.lead.list.form.operator.notbetween',
                'expr'        => 'notBetween',
                'negate_expr' => 'between',
                'hide'        => true,
            ],
            $operators['!between']
        );

        $this->assertSame(
            [
                'label'       => 'mautic.core.operator.starts.with',
                'expr'        => 'startsWith',
                'negate_expr' => 'startsWith',
            ],
            $operators['startsWith']
        );

        $this->assertSame(
            [
                'label'       => 'mautic.lead.list.form.operator.in',
                'expr'        => 'in',
                'negate_expr' => 'notIn',
            ],
            $operators['in']
        );
    }

    public function testOnGenerateSegmentFiltersAddCustomFieldsForBooleanTypes(): void
    {
        $field = new LeadField();
        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);

        $field->setType('boolean');
        $field->setLabel('Test Bool');
        $field->setAlias('test_bool');
        $field->setProperties([
            'no'  => 'No',
            'yes' => 'Yes',
        ]);

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->once())
            ->method('getOperatorsForFieldType')
            ->with('boolean')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);

        $this->assertSame(
            [
                'lead' => [
                    'test_bool' => [
                        'label'      => 'Test Bool',
                        'properties' => [
                            'no'   => 'No',
                            'yes'  => 'Yes',
                            'type' => 'boolean',
                            'list' => [
                                'No'  => 0,
                                'Yes' => 1,
                            ],
                        ],
                        'object'    => 'lead',
                        'operators' => [
                            'equals'    => '=',
                            'not equal' => '!=',
                        ],
                    ],
                ],
            ],
            $event->getChoices()
        );
    }

    public function testOnGenerateSegmentFiltersAddCustomFieldsForSelectTypes(): void
    {
        $field = new LeadField();
        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);

        $field->setType('select');
        $field->setLabel('Test Select');
        $field->setAlias('test_select');
        $field->setProperties([
            'list' => [
                'one' => 'One',
                'two' => 'Two',
            ],
        ]);

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->once())
            ->method('getOperatorsForFieldType')
            ->with('select')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);

        $this->assertSame(
            [
                'lead' => [
                    'test_select' => [
                        'label'      => 'Test Select',
                        'properties' => [
                            'list' => [
                                'One' => 'one',
                                'Two' => 'two',
                            ],
                            'type' => 'select',
                        ],
                        'object'    => 'lead',
                        'operators' => [
                            'equals'    => '=',
                            'not equal' => '!=',
                        ],
                    ],
                ],
            ],
            $event->getChoices()
        );
    }

    public function testOnGenerateSegmentFiltersAddCustomFieldsForCountryTypes(): void
    {
        $field = new LeadField();
        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);

        $field->setType('country');
        $field->setLabel('Test Country');
        $field->setAlias('test_country');

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->once())
            ->method('getOperatorsForFieldType')
            ->with('country')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->once())
            ->method('getChoicesForField')
            ->with('country')
            ->willReturn(
                [
                    'Czech Republic'  => 'Czech Republic',
                    'Slovak Republic' => 'Slovak Republic',
                ]
            );

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);

        $this->assertSame(
            [
                'lead' => [
                    'test_country' => [
                        'label'      => 'Test Country',
                        'properties' => [
                            'type' => 'country',
                            'list' => [
                                'Czech Republic'  => 'Czech Republic',
                                'Slovak Republic' => 'Slovak Republic',
                            ],
                        ],
                        'object'    => 'lead',
                        'operators' => [
                            'equals'    => '=',
                            'not equal' => '!=',
                        ],
                    ],
                ],
            ],
            $event->getChoices()
        );
    }

    public function testOnGenerateSegmentFiltersAddCustomFieldsForTextTypes(): void
    {
        $field = new LeadField();
        $event = new LeadListFiltersChoicesEvent([], [], $this->translator);

        $field->setType('text');
        $field->setLabel('Test Text');
        $field->setAlias('test_text');
        $field->setObject('company');

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->once())
            ->method('getOperatorsForFieldType')
            ->with('text')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->once())
            ->method('getChoicesForField')
            ->with('text')
            ->willThrowException(new ChoicesNotFoundException());

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);

        $this->assertSame(
            [
                'company' => [
                    'test_text' => [
                        'label'      => 'Test Text',
                        'properties' => [
                            'type' => 'text',
                        ],
                        'object'    => 'company',
                        'operators' => [
                            'equals'    => '=',
                            'not equal' => '!=',
                        ],
                    ],
                ],
            ],
            $event->getChoices()
        );
    }

    public function testOnGenerateSegmentFiltersAddStaticFields(): void
    {
        // Only displays on segment actions
        $request = new Request();
        $request->attributes->set('_route', 'mautic_segment_action');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsForFieldType')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsIncluding')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->any())
            ->method('getChoicesForField')
            ->willReturn(
                [
                    'Choice A' => 'choice_a',
                    'Choice B' => 'choice_b',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->subscriber->onGenerateSegmentFiltersAddStaticFields($event);

        $choices = $event->getChoices();

        // Test for some random choices:
        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.date_identified',
                'properties' => [
                    'type' => 'date',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['date_identified']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.device_model',
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['device_model']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.dnc_manual_email',
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        'Choice A' => 'choice_a',
                        'Choice B' => 'choice_b',
                    ],
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['dnc_manual_email']
        );
    }

    public function testOnGenerateSegmentFiltersAddBehaviors(): void
    {
        // Only displays on segment actions
        $request = new Request();
        $request->attributes->set('_route', 'mautic_segment_action');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsForFieldType')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsIncluding')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->any())
            ->method('getChoicesForField')
            ->willReturn(
                [
                    'Choice A' => 'choice_a',
                    'Choice B' => 'choice_b',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->subscriber->onGenerateSegmentFiltersAddBehaviors($event);

        $choices = $event->getChoices();

        // Test for some random choices:
        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.lead_email_received',
                'object'     => 'lead',
                'properties' => [
                    'type' => 'lead_email_received',
                    'list' => [
                        'Choice A' => 'choice_a',
                        'Choice B' => 'choice_b',
                    ],
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
            ],
            $choices['behaviors']['lead_email_received']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.visited_url_count',
                'properties' => [
                    'type' => 'number',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['behaviors']['hit_url_count']
        );
    }

    public function testOnlyCustomFieldsAreLoadedForNonSegmentRoutes(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'mautic_dynamicContent_action');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $field = new LeadField();
        $field->setType('select');
        $field->setLabel('Test Select');
        $field->setAlias('test_select');
        $field->setProperties([
            'list' => [
                'one' => 'One',
                'two' => 'Two',
            ],
        ]);

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsForFieldType')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsIncluding')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->any())
            ->method('getChoicesForField')
            ->willReturn(
                [
                    'Choice A' => 'choice_a',
                    'Choice B' => 'choice_b',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);
        $this->subscriber->onGenerateSegmentFiltersAddStaticFields($event);
        $this->subscriber->onGenerateSegmentFiltersAddBehaviors($event);

        $choices = $event->getChoices();

        // Only custom fields should be shown
        Assert::assertArrayHasKey('lead', $choices);
        Assert::assertArrayHasKey('test_select', $choices['lead']);

        // Static fields should not be included
        Assert::assertArrayNotHasKey('utm_source', $choices['lead']);

        // Behaviors should not be included
        Assert::assertArrayNotHasKey('behaviors', $choices);
    }

    public function testOnGenerateSegmentFiltersAddCustomFieldsForTextTypesForValueAjaxRequest(): void
    {
        // Only displays on segment actions
        $request = new Request();
        $request->attributes->set('action', 'loadSegmentFilterForm');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $field = new LeadField();
        $field->setType('text');
        $field->setLabel('Test Text');
        $field->setAlias('test_text');
        $field->setObject('company');

        $this->leadFieldRepository->expects($this->once())
            ->method('getListablePublishedFields')
            ->willReturn(new ArrayCollection([$field]));

        $this->typeOperatorProvider->expects($this->once())
            ->method('getOperatorsForFieldType')
            ->with('text')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->once())
            ->method('getChoicesForField')
            ->with('text')
            ->willThrowException(new ChoicesNotFoundException());

        $this->subscriber->onGenerateSegmentFiltersAddCustomFields($event);

        $this->assertSame(
            [
                'company' => [
                    'test_text' => [
                        'label'      => 'Test Text',
                        'properties' => [
                            'type' => 'text',
                        ],
                        'object'    => 'company',
                        'operators' => [
                            'equals'    => '=',
                            'not equal' => '!=',
                        ],
                    ],
                ],
            ],
            $event->getChoices()
        );
    }

    public function testOnGenerateSegmentFiltersAddStaticFieldsForValueAjaxRequest(): void
    {
        // Only displays on segment actions
        $request = new Request();
        $request->attributes->set('action', 'loadSegmentFilterForm');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsForFieldType')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsIncluding')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->any())
            ->method('getChoicesForField')
            ->willReturn(
                [
                    'Choice A' => 'choice_a',
                    'Choice B' => 'choice_b',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->subscriber->onGenerateSegmentFiltersAddStaticFields($event);

        $choices = $event->getChoices();

        // Test for some random choices:
        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.date_identified',
                'properties' => [
                    'type' => 'date',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['date_identified']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.device_model',
                'properties' => [
                    'type' => 'text',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['device_model']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.dnc_manual_email',
                'properties' => [
                    'type' => 'boolean',
                    'list' => [
                        'Choice A' => 'choice_a',
                        'Choice B' => 'choice_b',
                    ],
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['lead']['dnc_manual_email']
        );
    }

    public function testOnGenerateSegmentFiltersAddBehaviorsForValueAjaxRequest(): void
    {
        // Only displays on segment actions
        $request = new Request();
        $request->attributes->set('action', 'loadSegmentFilterForm');

        $event = new LeadListFiltersChoicesEvent([], [], $this->translator, $request);

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsForFieldType')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->typeOperatorProvider->expects($this->any())
            ->method('getOperatorsIncluding')
            ->willReturn(
                [
                    'equals'    => '=',
                    'not equal' => '!=',
                ]
            );

        $this->fieldChoicesProvider->expects($this->any())
            ->method('getChoicesForField')
            ->willReturn(
                [
                    'Choice A' => 'choice_a',
                    'Choice B' => 'choice_b',
                ]
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->subscriber->onGenerateSegmentFiltersAddBehaviors($event);

        $choices = $event->getChoices();

        // Test for some random choices:
        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.lead_email_received',
                'object'     => 'lead',
                'properties' => [
                    'type' => 'lead_email_received',
                    'list' => [
                        'Choice A' => 'choice_a',
                        'Choice B' => 'choice_b',
                    ],
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
            ],
            $choices['behaviors']['lead_email_received']
        );

        $this->assertSame(
            [
                'label'      => 'mautic.lead.list.filter.visited_url_count',
                'properties' => [
                    'type' => 'number',
                ],
                'operators' => [
                    'equals'    => '=',
                    'not equal' => '!=',
                ],
                'object' => 'lead',
            ],
            $choices['behaviors']['hit_url_count']
        );
    }
}
