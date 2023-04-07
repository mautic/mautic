<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\EventListener\ReportNormalizeSubscriber;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportDataEvent;
use PHPUnit\Framework\TestCase;

class ReportNormalizeSubscriberTest extends TestCase
{
    /**
     * @dataProvider normalizeData
     *
     * @param array<int, array<string, array<string, array<string, array<int,string>>|string>|string>> $properties
     */
    public function testOnReportDisplay(string $value, string $type, array $properties, string $expected): void
    {
        $fieldModel    = $this->createMock(FieldModel::class);

        $fields = [
            'field1' => [
                'alias'      => 'field1',
                'type'       => $type,
                'properties' => $properties,
            ],
        ];

        $leadRepository = $this->createMock(LeadFieldRepository::class);
        $leadRepository->method('getFields')->willReturn($fields);
        $fieldModel->method('getRepository')->willReturn($leadRepository);

        $rows = [
            [
                'field1' => $value,
            ],
        ];

        $report = new Report();
        $report->setColumns(['l.firstname' => 'l.firstname']);
        $event      = new ReportDataEvent($report, $rows, [], []);
        $subscriber = new ReportNormalizeSubscriber($fieldModel);
        $subscriber->onReportDisplay($event);

        $this->assertEquals(
            [
                [
                    'field1' => $expected,
                ],
            ],
            $event->getData()
        );
    }

    /**
     * @return array<int, array<string, array<string, array<string, array<int,string>>|string>|string>> $properties
     */
    public function normalizeData(): array
    {
        return [
            // Test for boolean custom field
            [
                'value'      => 'yes',
                'type'       => 'boolean',
                'properties' => [
                    'yes' => 'True',
                    'no'  => 'False',
                ],
                'expected'   => 'True',
            ],

            // Test for select custom field
            [
                'value'      => '2',
                'type'       => 'select',
                'properties' => [
                    'list' => [
                        'list' => [
                            1 => 'Option 1',
                            2 => 'Option 2',
                        ],
                    ],
                ],
                'expected'   => 'Option 2',
            ],

            // Test for multiselect custom field
            [
                'value'      => '1|3',
                'type'       => 'multiselect',
                'properties' => [
                    'list' => [
                        'list' => [
                            1 => 'Option 1',
                            2 => 'Option 2',
                            3 => 'Option 3',
                        ],
                    ],
                ],
                'expected'   => 'Option 1|Option 3',
            ],
        ];
    }
}
