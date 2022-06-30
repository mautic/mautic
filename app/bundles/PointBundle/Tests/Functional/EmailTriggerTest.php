<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PointBundle\Entity\Trigger;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class EmailTriggerTest extends MauticMysqlTestCase
{
    /**
     * @var Email
     */
    private $email;

    public function testPreviewSendEmailToUser()
    {
        $email = new Email();
        $email->setName('Some name');
        $email->setSubject('Some subject');
        $email->setTemplate('Blank');
        $email->setCustomHtml('Some html');
        $this->em->persist($email);
        $this->em->flush();

        $trigger = new Trigger();
        $trigger->setName('Email Trigger');
        $this->em->persist($trigger);

        $triggerEvent = new \Mautic\PointBundle\Entity\TriggerEvent();
        $triggerEvent->setTrigger($trigger);
        $triggerEvent->setName('Send email to user');
        $triggerEvent->setType('email.send_to_user');
        $triggerEvent->setProperties(['useremail' => ['email' => $email->getId()]]);
        $this->em->persist($triggerEvent);

        $this->em->flush();

        $this->em->clear();

        $this->client->request(Request::METHOD_GET, '/s/points/triggers/edit/'.$trigger->getId());
        self::assertTrue($this->client->getResponse()->isSuccessful());

        $uri     = sprintf('/s/points/triggers/events/edit/%s?triggerId=%s', $triggerEvent->getId(), $trigger->getId());
        $crawler = $this->client->request(Request::METHOD_GET, $uri, [], [], $this->createAjaxHeaders());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $crawler      = new Crawler($responseData['newContent'], $this->client->getInternalRequest()->getUri());
//
//        $buttonCrawler = $crawler
//            ->selectImage('Events')
//            ->selectButton('Add an event')->filter('.dropdown-menu .list-group-item')->attr('href');
//
    }
}
