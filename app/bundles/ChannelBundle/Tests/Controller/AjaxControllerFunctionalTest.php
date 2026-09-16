<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Controller;

use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\HttpFoundation\Request;

final class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('sendToDncProvider')]
    public function testGetMarketingMessageSendToDncStatusAction(bool $sendToDnc): void
    {
        $email   = $this->createEmail($sendToDnc);
        $channel = $this->createChannel($email);
        $message = $this->createMessage($channel);

        $this->em->flush();
        $this->em->clear();

        $this->assertAjaxResponse($message, $email);
    }

    public function testGetMarketingMessageSendToDncStatusWithoutEmailAction(): void
    {
        $channel = $this->createChannel(null);
        $message = $this->createMessage($channel);

        $this->em->flush();
        $this->em->clear();

        $this->assertAjaxResponse($message, null);
    }

    public static function sendToDncProvider(): \Generator
    {
        yield [true];
        yield [false];
    }

    private function createEmail(bool $sendToDnc): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSendToDnc($sendToDnc);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    private function createChannel(?Email $email): Channel
    {
        $channel = new Channel();
        $channel->setChannel('email');
        $channel->setChannelId($email?->getId());
        $channel->setIsEnabled(true);
        $this->em->persist($channel);

        return $channel;
    }

    private function createMessage(Channel $channel): Message
    {
        $message = new Message();
        $message->setName('API message');
        $message->addChannel($channel);
        $this->em->persist($message);

        return $message;
    }

    private function assertAjaxResponse(Message $message, ?Email $email): void
    {
        $payload = [
            'action' => 'channel:getMarketingMessageSendToDncStatus',
            'id'     => $message->getId(),
        ];

        $this->client->request(Request::METHOD_GET, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $this->assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        if (null !== $email) {
            $this->assertSame($email->getSendToDnc() ? 'Yes' : 'No', $response['sendToDncText']);
            $this->assertSame($email->getSendToDnc(), $response['sendToDncStatus']);
        } else {
            $this->assertArrayNotHasKey('sendToDncText', $response);
            $this->assertArrayNotHasKey('sendToDncStatus', $response);
        }
    }
}
