<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Controller;

use Mautic\ChannelBundle\Entity\Channel;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider sendToDncProvider
     */
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

    public function sendToDncProvider(): \Generator
    {
        yield [true];
        yield [false];
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createEmail(bool $sendToDnc): Email
    {
        $email = new Email();
        $email->setName('Test');
        $email->setSendToDnc($sendToDnc);
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function createChannel(?Email $email): Channel
    {
        $channel = new Channel();
        $channel->setChannel('email');
        $channel->setChannelId($email?->getId());
        $channel->setIsEnabled(true);
        $this->em->persist($channel);

        return $channel;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        Assert::assertTrue($this->client->getResponse()->isOk());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        if (null !== $email) {
            Assert::assertSame($email->getSendToDnc() ? 'Yes' : 'No', $response['sendToDncText']);
            Assert::assertSame($email->getSendToDnc(), $response['sendToDncStatus']);
        } else {
            Assert::assertArrayNotHasKey('sendToDncText', $response);
            Assert::assertArrayNotHasKey('sendToDncStatus', $response);
        }
    }
}
