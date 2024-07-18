<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailExampleFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['mailer_spool_type'] = 'file';

        parent::setUp();
    }

    public function testSendExampleEmailWithContact(): void
    {
        $lead  = $this->createLead();
        $email = $this->createEmail();
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        Assert::assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => 'somebody',
            'example_send[contact_id]'      => $lead->getId(),
        ]);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];

        // Asserting email data
        Assert::assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        Assert::assertStringContainsString(
            'Contact emails is test@domain.tld',
            $message->getBody()->toString()
        );
    }

    public function testSendExampleEmailWithOutContact(): void
    {
        $email = $this->createEmail();
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        self::assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];

        // Asserting email data
        Assert::assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        Assert::assertStringContainsString('Contact emails is [Email]', $message->getBody()->toString());
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setCustomHtml('Contact emails is {contactfield=email}');
        $this->em->persist($email);

        return $email;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('test@domain.tld');
        $this->em->persist($lead);

        return $lead;
    }
}
