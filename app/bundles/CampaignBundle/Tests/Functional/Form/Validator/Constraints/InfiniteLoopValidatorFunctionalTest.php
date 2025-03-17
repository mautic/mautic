<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Form\Validator\Constraints;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

final class InfiniteLoopValidatorFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider delayDataProvider
     */
    public function testSubmitCampaignActionVariousDelayOptions(string $triggerMode, int $triggerInterval, string $triggerIntervalUnit, int $success, string $expectedString): void
    {
        $uri = '/s/campaigns/events/new?type=campaign.addremovelead&eventType=action&campaignId=mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775&anchor=leadsource&anchorEventType=source';
        $this->client->xmlHttpRequest('GET', $uri);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();
        $form->setValues(
            [
                'campaignevent[anchor]'              => 'leadsource',
                'campaignevent[properties][addTo]'   => ['this'],
                'campaignevent[type]'                => 'campaign.addremovelead',
                'campaignevent[eventType]'           => 'action',
                'campaignevent[anchorEventType]'     => 'source',
                'campaignevent[triggerMode]'         => $triggerMode,
                'campaignevent[triggerInterval]'     => $triggerInterval,
                'campaignevent[triggerIntervalUnit]' => $triggerIntervalUnit,
                'campaignevent[campaignId]'          => 'mautic_89f7f52426c1dff3daa3beaea708a6b39fe7a775',
            ]
        );

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        Assert::assertSame($success, $responseData['success'], $response->getContent());

        if ($expectedString) {
            Assert::assertStringContainsString($expectedString, $responseData['newContent']);
        }
    }

    /**
     * @return iterable<string,array<string|int>>
     */
    public function delayDataProvider(): iterable
    {
        yield 'The immediate mode cannot be allowed otherwise the contacts will loop too fast for no reason' => [
            'immediate',
            1,
            'i',
            0,
            'Campaign cannot restart itself without a delay. Please add at least 30 minute delay.',
        ];

        yield 'The interval mode with less than 30 minutes cannot be allowed' => [
            'interval',
            29,
            'i',
            0,
            'Your delay is only 29 minutes. It must be at least 30 minutes.',
        ];

        yield 'The interval mode with 30 minutes or more should be allowed' => [
            'interval',
            30,
            'i',
            1,
            '',
        ];
    }

    /**
     * @dataProvider delayDataProvider
     */
    public function testValidationViaCampaignApi(string $triggerMode, int $triggerInterval, string $triggerIntervalUnit, int $success, string $expectedString): void
    {
        $segment = new LeadList();
        $segment->setName('Test');
        $segment->setPublicName('Test');
        $segment->setAlias('test');
        $this->em->persist($segment);
        $this->em->flush();

        $payload = [
            'name'   => 'Loop test',
            'events' => [
                [
                    'id'         => 'new_30',
                    'name'       => 'Change campaigns',
                    'type'       => 'campaign.addremovelead',
                    'eventType'  => 'action',
                    'properties' => [
                        'canvasSettings' => [
                            'droppedX' => '833',
                            'droppedY' => '155',
                        ],
                        'triggerMode'         => $triggerMode,
                        'triggerInterval'     => $triggerInterval,
                        'triggerIntervalUnit' => $triggerIntervalUnit,
                        'anchor'              => 'leadsource',
                        'properties'          => [
                            'addTo' => [
                                'this',
                            ],
                        ],
                        'type'            => 'campaign.addremovelead',
                        'eventType'       => 'action',
                        'anchorEventType' => 'source',
                        'campaignId'      => 'mautic_5d0923689420c9d3981255dc56b6308b92db82c2',
                        '_token'          => 'pDmdgUFBm2tj-Vu8IoAfiaVNYy8sdBNjwrGtO9Igut8',
                        'addTo'           => ['this'],
                        'removeFrom'      => [],
                    ],
                    'triggerMode'         => $triggerMode,
                    'triggerInterval'     => $triggerInterval,
                    'triggerIntervalUnit' => $triggerIntervalUnit,
                    'decisionPath'        => null,
                    'parent'              => null,
                    'children'            => [],
                ],
                [
                    'id'         => 'new_31',
                    'name'       => 'Change points',
                    'type'       => 'lead.changepoints',
                    'eventType'  => 'action',
                    'properties' => [
                        'canvasSettings' => [
                            'droppedX' => '933',
                            'droppedY' => '255',
                        ],
                        'triggerMode'         => $triggerMode,
                        'triggerInterval'     => $triggerInterval,
                        'triggerIntervalUnit' => $triggerIntervalUnit,
                        'anchor'              => 'leadsource',
                        'properties'          => [
                            'points' => 2,
                        ],
                        'type'            => 'lead.changepoints',
                        'eventType'       => 'action',
                        'anchorEventType' => 'source',
                        'campaignId'      => 'mautic_5d0923689420c9d3981255dc56b6308b92db82c2',
                        '_token'          => 'pDmdgUFBm2tj-Vu8IoAfiaVNYy8sdBNjwrGtO9Igut8',
                        'points'          => 2,
                    ],
                    'triggerMode'         => $triggerMode,
                    'triggerInterval'     => $triggerInterval,
                    'triggerIntervalUnit' => $triggerIntervalUnit,
                    'decisionPath'        => null,
                    'parent'              => null,
                    'children'            => [],
                ],
            ],
            'lists'          => [$segment->getId()],
            'canvasSettings' => [
                'nodes' => [
                    [
                        'id'        => 'new_30',
                        'positionX' => 833,
                        'positionY' => 155,
                    ],
                    [
                        'id'        => 'new_31',
                        'positionX' => 833,
                        'positionY' => 155,
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => 933,
                        'positionY' => 50,
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_30',
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_31',
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ],
        ];

        $expectedStatusCode = $success ? 201 : 422;

        $this->client->request('POST', '/api/campaigns/new', $payload);
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeSame($expectedStatusCode, $response->getContent());

        if ($expectedString) {
            Assert::assertStringContainsString($expectedString, $response->getContent());
        }
    }
}
