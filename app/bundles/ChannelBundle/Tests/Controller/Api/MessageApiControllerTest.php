<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Controller\Api;

use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class MessageApiControllerTest extends MauticMysqlTestCase
{
    public function testCreateMessage(): void
    {
        $payloadJson = <<<'JSON'
{
    "name": "API message",
    "description": "Marketing message created via API functional test",
    "channels": {
        "email": {
            "channel": "email",
            "channelId": 12,
            "isEnabled": true
        }
    }
}
JSON;

        $payloadArray = json_decode($payloadJson, true);

        $this->client->request('POST', '/api/messages/new', $payloadArray);
        $responseJson = $this->client->getResponse()->getContent();
        Assert::assertSame(201, $this->client->getResponse()->getStatusCode(), $responseJson);
        $this->assertMessagePayload($payloadArray, json_decode($responseJson, true)['message'], $responseJson);
    }

    /**
     * @dataProvider patchProvider
     *
     * @param mixed[] $payload
     * @param mixed[] $expectedResponsePayload
     */
    public function testEditMessageWithPatch(array $payload, array $expectedResponsePayload): void
    {
        $channel = new Channel();
        $channel->setChannel('email');
        $channel->setChannelId(12);
        $channel->setIsEnabled(true);

        $message = new Message();
        $message->setName('API message');
        $message->addChannel($channel);

        $this->em->persist($channel);
        $this->em->persist($message);
        $this->em->flush();
        $this->em->clear();

        $patchPayload = ['id' => $message->getId()] + $payload;
        $this->client->request('PATCH', "/api/messages/{$message->getId()}/edit", $patchPayload);
        $responseJson = $this->client->getResponse()->getContent();
        Assert::assertSame(200, $this->client->getResponse()->getStatusCode(), $responseJson);
        $this->assertMessagePayload(
            ['id' => $message->getId()] + $expectedResponsePayload,
            json_decode($responseJson, true)['message'],
            $responseJson
        );
    }

    /**
     * Note: the ID is added to the payload automatically in the test.
     *
     * @return iterable<mixed[]>
     */
    public function patchProvider(): iterable
    {
        yield [
            [
                'name' => 'API message (updated)',
            ],
            [
                'name'        => 'API message (updated)',
                'description' => null,
                'channels'    => [
                    'email' => [
                        'channel'   => 'email',
                        'channelId' => 12,
                        'isEnabled' => true,
                    ],
                ],
            ],
        ];

        yield [
            [
                'description' => 'Description (updated)',
                'channels'    => [
                    'email' => [
                        'channel'   => 'email',
                        'channelId' => 13,
                        'isEnabled' => false,
                    ],
                ],
            ],
            [
                'name'        => 'API message',
                'description' => 'Description (updated)',
                'channels'    => [
                    'email' => [
                        'channel'   => 'email',
                        'channelId' => 13,
                        'isEnabled' => false,
                    ],
                ],
            ],
        ];
    }

    public function testEditMessagesWithPatch(): void
    {
        $channel1 = new Channel();
        $channel1->setChannel('email');
        $channel1->setChannelId(12);
        $channel1->setIsEnabled(true);

        $message1 = new Message();
        $message1->setName('API message 1');
        $message1->addChannel($channel1);

        $channel2 = new Channel();
        $channel2->setChannel('email');
        $channel2->setChannelId(13);
        $channel2->setIsEnabled(true);

        $message2 = new Message();
        $message2->setName('API message 2');
        $message2->addChannel($channel2);

        $this->em->persist($channel1);
        $this->em->persist($channel2);
        $this->em->persist($message1);
        $this->em->persist($message2);
        $this->em->flush();
        $this->em->clear();

        $patchPayload = [
            ['id' => $message1->getId(), 'name' => 'API message 1 (updated)'],
            ['id' => $message2->getId(), 'channels' => ['email' => ['channelId' => 14, 'isEnabled' => false]]],
        ];
        $this->client->request('PATCH', '/api/messages/batch/edit', $patchPayload);
        $responseJson = $this->client->getResponse()->getContent();
        Assert::assertSame(200, $this->client->getResponse()->getStatusCode(), $responseJson);
        $responseArray = json_decode($responseJson, true);
        $this->assertMessagePayload(
            [
                'id'          => $message1->getId(),
                'name'        => 'API message 1 (updated)',
                'description' => null,
                'channels'    => [
                    'email' => [
                        'channel'   => 'email',
                        'channelId' => 12,
                        'isEnabled' => true,
                    ],
                ],
            ],
            $responseArray['messages'][0],
            $responseJson
        );
        $this->assertMessagePayload(
            [
                'id'          => $message2->getId(),
                'name'        => 'API message 2',
                'description' => null,
                'channels'    => [
                    'email' => [
                        'channel'   => 'email',
                        'channelId' => 14,
                        'isEnabled' => false,
                    ],
                ],
            ],
            $responseArray['messages'][1],
            $responseJson
        );
    }

    /**
     * @param mixed[] $expectedPayload
     * @param mixed[] $actualPayload
     */
    private function assertMessagePayload(array $expectedPayload, array $actualPayload, string $deliveredPayloadJson): void
    {
        Assert::assertSame($expectedPayload['name'], $actualPayload['name'], $deliveredPayloadJson);
        Assert::assertSame($expectedPayload['description'], $actualPayload['description'], $deliveredPayloadJson);
        Assert::assertCount(count($expectedPayload['channels']), $actualPayload['channels'], $deliveredPayloadJson);
        Assert::assertGreaterThan(0, $actualPayload['id'], $deliveredPayloadJson);

        Assert::assertSame($expectedPayload['channels']['email']['channel'], $actualPayload['channels'][0]['channel'], $deliveredPayloadJson);
        Assert::assertSame($expectedPayload['channels']['email']['channelId'], $actualPayload['channels'][0]['channelId'], $deliveredPayloadJson);
        Assert::assertSame($expectedPayload['channels']['email']['isEnabled'], $actualPayload['channels'][0]['isEnabled'], $deliveredPayloadJson);
        Assert::assertGreaterThan(0, $actualPayload['channels'][0]['id'], $deliveredPayloadJson);
    }
}
