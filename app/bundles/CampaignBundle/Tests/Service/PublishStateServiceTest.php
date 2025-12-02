<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Service;

use Mautic\CampaignBundle\DTO\PublishStateDateRange;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Service\PublishStateService;
use Mautic\CampaignBundle\Tests\CampaignAuditLogTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class PublishStateServiceTest extends MauticMysqlTestCase
{
    use CampaignAuditLogTrait;

    /**
     * @param array<array{dateAdded: string, details: array<string, array<int, mixed>>}> $auditLogs
     * @param array<array{fromDate: ?string, toDate: ?string}>                           $expectedunpublishedranges
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unpublishStateDataProvider')]
    public function testUnpublishStateCompilationFromAuditLog(array $auditLogs, array $expectedunpublishedranges, ?string $expectedLastPublishedDate, ?int $expectedUnpublishedSecondsSinceCampaignCreated = null): void
    {
        $campaign     = new Campaign();
        $lastAuditLog = end($auditLogs);

        if ($lastAuditLog && isset($lastAuditLog['details']['isPublished'][1])) {
            $campaign->setIsPublished($lastAuditLog['details']['isPublished'][1]);
        }

        if ($lastAuditLog && isset($lastAuditLog['details']['publishUp'][1])) {
            $campaign->setPublishUp(new \DateTime($lastAuditLog['details']['publishUp'][1]));
        }

        if ($lastAuditLog && isset($lastAuditLog['details']['publishDown'][1])) {
            $campaign->setPublishDown(new \DateTime($lastAuditLog['details']['publishDown'][1]));
        }

        $campaign->setName('Test Campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        $this->saveAuditLogs($this->em, $auditLogs, $campaign);

        $unpublishStateService = $this->getContainer()->get(PublishStateService::class);
        \assert($unpublishStateService instanceof PublishStateService);

        Assert::assertSame($expectedunpublishedranges, array_map(
            fn (PublishStateDateRange $range) => [
                'fromDate' => $range->getFromDate()->format(DateTimeHelper::FORMAT_DB),
                'toDate'   => $range->getToDate()?->format(DateTimeHelper::FORMAT_DB),
            ], $unpublishStateService->generateUnpublishDateRanges($campaign)
        ));

        Assert::assertSame(
            $expectedLastPublishedDate,
            $unpublishStateService->getLastPublishDate($campaign)?->format(DateTimeHelper::FORMAT_DB)
        );

        if (null === $expectedUnpublishedSecondsSinceCampaignCreated) {
            return;
        }

        Assert::assertSame(
            $expectedUnpublishedSecondsSinceCampaignCreated,
            $unpublishStateService->getUnublishedSecondsSince($campaign, new \DateTimeImmutable($auditLogs[0]['dateAdded']))
        );
    }

    public static function unpublishStateDataProvider(): \Generator
    {
        yield 'Campaign that is published' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, true],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [],
            'expectedLastPublishedDate' => '2025-01-01 00:00:00',
        ];

        yield 'Campaign that is unpublished' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => null,
        ];

        yield 'Campaign that was unpublished and then published' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-10 00:00:00',
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-10 00:00:00',
        ];

        yield 'Campaign that was unpublished, published and then unpublished' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-11 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-10 00:00:00',
                ],
                [
                    'fromDate' => '2025-01-11 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-10 00:00:00',
        ];

        yield 'Campaign that was unpublished and then published should obey the publishDown date' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                        'publishUp'   => [null, '2025-01-03 00:00:00'], // already in the past when actually published.
                        'publishDown' => [null, '2025-02-01 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-10 00:00:00',
                ],
                [
                    'fromDate' => '2025-02-01 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-10 00:00:00',
        ];

        yield 'Campaign that was scheduled to be unpublished but was unpublished before that' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                        'publishUp'   => [null, '2025-01-11 00:00:00'],
                        'publishDown' => [null, '2025-02-01 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-12 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-11 00:00:00',
                ],
                [
                    'fromDate' => '2025-01-12 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-11 00:00:00',
        ];

        yield 'Campaign that was unpublished with a publishUp and Down dates that were in the future when unpublished but up is less then down, published and unpublished again should have one date range' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                        'publishUp'   => [null, '2025-01-11 00:00:00'],
                        'publishDown' => [null, '2025-02-01 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-03-01 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-11 00:00:00',
                ],
                [
                    'fromDate' => '2025-02-01 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-11 00:00:00',
        ];

        yield 'Campaign that was unpublished with a publishUp and Down dates that were in the future when unpublished, published and unpublished again should have one date range' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                        'publishUp'   => [null, '2025-02-11 00:00:00'],
                        'publishDown' => [null, '2025-02-01 00:00:00'], // This is invalid as publishDown is before publishUp but possible to configure!
                    ],
                ],
                [
                    'dateAdded' => '2025-03-01 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-02-11 00:00:00',
                ],
                [
                    'fromDate' => '2025-02-01 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-02-11 00:00:00',
        ];

        yield 'Campaign that was published but the publishUp and Down dates changed several times during the publish state' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, true],
                        'publishUp'   => [null, '2025-01-04 00:00:00'], // This range never gets to the publish up date, the next will overwrite it.
                        'publishDown' => [null, '2025-01-11 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-03 00:00:00',
                    'details'   => [
                        'isPublished' => [true, true],
                        'publishUp'   => [null, '2025-01-05 00:00:00'],
                        'publishDown' => [null, '2025-01-12 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [true, true],
                        'publishUp'   => [null, '2025-01-20 00:00:00'],
                        'publishDown' => [null, '2025-01-25 00:00:00'],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-03 00:00:00',
                    'toDate'   => '2025-01-05 00:00:00',
                ],
                [
                    'fromDate' => '2025-01-10 00:00:00',
                    'toDate'   => '2025-01-20 00:00:00',
                ],
                [
                    'fromDate' => '2025-01-25 00:00:00',
                    'toDate'   => null,
                ],
            ],
            'expectedLastPublishedDate' => '2025-01-20 00:00:00',
        ];

        yield 'Campaign that was unpublished with a publishUp and Down dates several times' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [
                        'isPublished' => [null, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-03 00:00:00',
                    'details'   => [
                        'isPublished' => [false, false],
                        'publishUp'   => [null, '2025-02-04 00:00:00'],
                        'publishDown' => [null, '2025-02-12 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-01-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                        'publishUp'   => [null, '2025-02-11 00:00:00'],
                        'publishDown' => [null, '2025-02-22 00:00:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-03-01 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-03-10 00:00:00',
                    'details'   => [
                        'isPublished' => [false, true],
                        'publishUp'   => [null, '2025-02-11 00:00:00'],
                        'publishDown' => [null, '2025-02-22 00:00:00'], // doesn't apply as it is in the past
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-02-11 00:00:00',
                ],
                [
                    'fromDate' => '2025-02-22 00:00:00',
                    'toDate'   => '2025-03-10 00:00:00',
                ],
            ],
            'expectedLastPublishedDate' => '2025-03-10 00:00:00',
        ];

        yield 'Campaign that was published when created has the isPublished missing.' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-01-01 00:00:00',
                    'details'   => [],
                ],
                [
                    'dateAdded' => '2025-01-03 00:00:00',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-01-01 00:00:00',
                    'toDate'   => '2025-01-03 00:00:00',
                ],
            ],
            'expectedLastPublishedDate' => null, // due to the publish down date
        ];

        yield 'Test also the same-day minutes apart changes' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-10-02 11:39:07',
                    'details'   => [
                        'publishUp' => [null, '2025-09-05T05:26:00-04:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-10-02 11:44:50',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-10-02 11:55:19',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-10-02 11:44:50',
                    'toDate'   => '2025-10-02 11:55:19',
                ],
            ],
            'expectedLastPublishedDate'                      => '2025-10-02 11:55:19',
            'expectedUnpublishedSecondsSinceCampaignCreated' => 629, // From 2025-10-02 11:44:50 to 2025-10-02 11:55:19
        ];

        yield 'Test specific usecase that caused troubles' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-10-17 11:31:00',
                    'details'   => [
                        'publishUp' => [null, '2025-10-17T07:31:00-04:00'],
                    ],
                ],
                [
                    'dateAdded' => '2025-10-17 11:34:52',
                    'details'   => [
                        'isPublished' => [true, false],
                    ],
                ],
                [
                    'dateAdded' => '2025-10-17 11:41:42',
                    'details'   => [
                        'isPublished' => [false, true],
                    ],
                ],
            ],
            'expectedunpublishedranges' => [
                [
                    'fromDate' => '2025-10-17 11:34:52',
                    'toDate'   => '2025-10-17 11:41:42',
                ],
            ],
            'expectedLastPublishedDate' => '2025-10-17 11:41:42',
        ];

        yield 'Test a campaign that was just created and published at the same time' => [
            'auditLogs' => [
                [
                    'dateAdded' => '2025-10-02 11:39:07',
                    'details'   => [],
                ],
            ],
            'expectedunpublishedranges' => [],
            'expectedLastPublishedDate' => '2025-10-02 11:39:07',
        ];
    }
}
