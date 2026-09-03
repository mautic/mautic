<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

final class CampaignSendWebhookFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    /**
     * @param array<int, array{label: string, value: string}> $inputData
     * @param array<int, array{label: string, value: string}> $expectedData
     */
    #[DataProvider('webhookFormDataProvider')]
    public function testWebhookFormSubmission(string $scenario, array $inputData, array $expectedData): void
    {
        $segment  = $this->createSegment('test-segment-'.uniqid(), []);
        $campaign = $this->createCampaign('Test Campaign');
        $campaign->addList($segment);
        $this->em->flush();

        $responseData = $this->submitWebhookEventForm($campaign, $inputData);
        $properties   = $responseData['event']['properties'];

        $this->assertCount(count($expectedData), $properties['additional_data']['list'], "Failed for scenario: $scenario");

        foreach ($expectedData as $index => $expected) {
            $this->assertEquals($expected['label'], $properties['additional_data']['list'][$index]['label']);
            $this->assertEquals($expected['value'], $properties['additional_data']['list'][$index]['value']);
        }
    }

    /**
     * @return iterable<string, array{string, array<int, array{label: string, value: string}>, array<int, array{label: string, value: string}>}>
     */
    public static function webhookFormDataProvider(): iterable
    {
        yield 'different labels with same value are both saved' => [
            'Different labels (paramA, paramB) with same value should save both entries',
            [
                ['label' => 'paramA', 'value' => 'sameValue'],
                ['label' => 'paramB', 'value' => 'sameValue'],
            ],
            [
                ['label' => 'paramA', 'value' => 'sameValue'],
                ['label' => 'paramB', 'value' => 'sameValue'],
            ],
        ];

        yield 'same label with different values keeps only one' => [
            'Same label (paramA) with different values should save only one entry',
            [
                ['label' => 'paramA', 'value' => 'value1'],
                ['label' => 'paramA', 'value' => 'value2'],
            ],
            [
                ['label' => 'paramA', 'value' => 'value2'],
            ],
        ];

        yield 'unique labels and values are all saved' => [
            'Unique labels and values should all be saved',
            [
                ['label' => 'email', 'value' => '{contactfield=email}'],
                ['label' => 'firstname', 'value' => '{contactfield=firstname}'],
            ],
            [
                ['label' => 'email', 'value' => '{contactfield=email}'],
                ['label' => 'firstname', 'value' => '{contactfield=firstname}'],
            ],
        ];
    }

    /**
     * @param array<int, array{label: string, value: string}> $inputData
     * @param array<int, array{label: string, value: string}> $expectedData
     */
    #[DataProvider('webhookApiDataProvider')]
    public function testWebhookEventViaCampaignApi(string $scenario, array $inputData, array $expectedData): void
    {
        $segment = $this->createSegment('test-segment-'.uniqid(), []);
        $this->em->flush();

        $payload = [
            'name'   => 'Webhook Campaign Test',
            'events' => [
                [
                    'id'         => 'new_1',
                    'name'       => 'Send Webhook',
                    'type'       => 'campaign.sendwebhook',
                    'eventType'  => 'action',
                    'properties' => [
                        'canvasSettings'      => ['droppedX' => '500', 'droppedY' => '200'],
                        'triggerMode'         => 'immediate',
                        'anchor'              => 'leadsource',
                        'anchorEventType'     => 'source',
                        'url'                 => 'https://example.com/webhook',
                        'method'              => 'post',
                        'timeout'             => 10,
                        'headers'             => [
                            'list' => [['label' => 'Content-Type', 'value' => 'application/json']],
                        ],
                        'additional_data' => ['list' => $inputData],
                    ],
                    'triggerMode'  => 'immediate',
                    'decisionPath' => null,
                    'parent'       => null,
                    'children'     => [],
                ],
            ],
            'lists'          => [$segment->getId()],
            'canvasSettings' => [
                'nodes' => [
                    ['id' => 'new_1', 'positionX' => 500, 'positionY' => 200],
                    ['id' => 'lists', 'positionX' => 500, 'positionY' => 50],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_1',
                        'anchors'  => ['source' => 'leadsource', 'target' => 'top'],
                    ],
                ],
            ],
        ];

        $this->client->request('POST', '/api/campaigns/new', $payload);
        $response = $this->client->getResponse();
        $this->assertSame(\Symfony\Component\HttpFoundation\Response::HTTP_CREATED, $response->getStatusCode(), (string) $response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $campaignId   = $responseData['campaign']['id'];

        $this->em->clear();

        $campaign = $this->em->find(Campaign::class, $campaignId);
        $events   = $campaign->getEvents();

        /** @var Event $event */
        $event      = $events->first();
        $properties = $event->getProperties();

        $this->assertCount(count($expectedData), $properties['additional_data']['list'], "Failed for scenario: $scenario");

        foreach ($expectedData as $index => $expected) {
            $this->assertEquals($expected['label'], $properties['additional_data']['list'][$index]['label']);
            $this->assertEquals($expected['value'], $properties['additional_data']['list'][$index]['value']);
        }
    }

    /**
     * @return iterable<string, array{string, array<int, array{label: string, value: string}>, array<int, array{label: string, value: string}>}>
     */
    public static function webhookApiDataProvider(): iterable
    {
        yield 'different labels with same value via API' => [
            'Different labels with same value should save both via API',
            [
                ['label' => 'paramA', 'value' => 'sameValue'],
                ['label' => 'paramB', 'value' => 'sameValue'],
            ],
            [
                ['label' => 'paramA', 'value' => 'sameValue'],
                ['label' => 'paramB', 'value' => 'sameValue'],
            ],
        ];

        yield 'unique labels and values via API' => [
            'Unique labels and values via API',
            [
                ['label' => 'email', 'value' => '{contactfield=email}'],
                ['label' => 'phone', 'value' => '{contactfield=phone}'],
            ],
            [
                ['label' => 'email', 'value' => '{contactfield=email}'],
                ['label' => 'phone', 'value' => '{contactfield=phone}'],
            ],
        ];
    }

    /**
     * @param array<int, array{label: string, value: string}> $additionalData
     *
     * @return array<string, mixed>
     */
    private function submitWebhookEventForm(Campaign $campaign, array $additionalData): array
    {
        $uri = sprintf(
            '/s/campaigns/events/new?type=campaign.sendwebhook&eventType=action&campaignId=mautic_%s&anchor=leadsource&anchorEventType=source',
            sha1((string) $campaign->getId())
        );

        $this->client->request('GET', $uri, [], [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk(), $response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
        $form         = $crawler->filterXPath('//form[@name="campaignevent"]')->form();

        $form->setValues([
            'campaignevent[anchor]'              => 'leadsource',
            'campaignevent[type]'                => 'campaign.sendwebhook',
            'campaignevent[eventType]'           => 'action',
            'campaignevent[anchorEventType]'     => 'source',
            'campaignevent[triggerMode]'         => 'immediate',
            'campaignevent[campaignId]'          => 'mautic_'.sha1((string) $campaign->getId()),
            'campaignevent[properties][url]'     => 'https://example.com/webhook',
            'campaignevent[properties][method]'  => 'post',
            'campaignevent[properties][timeout]' => '10',
        ]);

        $formValues                                                   = $form->getPhpValues();
        $formValues['campaignevent']['properties']['headers']['list'] = [
            ['label' => 'Content-Type', 'value' => 'application/json'],
        ];
        $formValues['campaignevent']['properties']['additional_data']['list'] = $additionalData;

        $this->client->request($form->getMethod(), $form->getUri(), $formValues, [], $this->createAjaxHeaders());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk(), $response->getContent());

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(1, $responseData['success'], (string) $response->getContent());

        return $responseData;
    }
}
