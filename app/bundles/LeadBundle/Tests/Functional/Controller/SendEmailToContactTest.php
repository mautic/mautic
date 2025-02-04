<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

final class SendEmailToContactTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testPreheaderConfigIsApplied(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $contact->setFirstname('John');

        $emailEntity = new Email();
        $emailEntity->setName('Email A');
        $emailEntity->setFromAddress('overwrite@address.com');
        $emailEntity->setFromName('Overwrite Name');
        $emailEntity->setSubject('Subject to overwrite');
        $emailEntity->setCustomHtml('<html><body><p>This should be overwritten by the form content</p></body></html>');
        $emailEntity->setPreheaderText('This is a preheader text');

        $this->em->persist($contact);
        $this->em->persist($emailEntity);
        $this->em->flush();

        // Fetch the form
        $this->client->request(Request::METHOD_GET, '/s/contacts/email/'.$contact->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $content     = $this->client->getResponse()->getContent();
        $content     = json_decode($content)->newContent;
        $crawler     = new Crawler($content, $this->client->getInternalRequest()->getUri());
        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();

        // Send email to contact
        $form->setValues([
            'lead_quickemail[fromname]'  => 'Admin',
            'lead_quickemail[from]'      => 'admin@test-beta.mautibot.com',
            'lead_quickemail[subject]'   => 'Some interesting subject for {contactfield=firstname}',
            'lead_quickemail[body]'      => '<html><body><p>Hey {contactfield=firstname}...</p></body></html>',
            'lead_quickemail[list]'      => 0,
            'lead_quickemail[templates]' => $emailEntity->getId(),
        ]);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $message = $this->getMailerMessagesByToAddress('john@doe.email')[0];
        $email   = $message->getBody()->toString();
        Assert::assertStringContainsString('Hey John...', $email);
        Assert::assertStringContainsString('<title>Some interesting subject for John</title>', $email);
        Assert::assertStringContainsString('Some interesting subject for John', $message->getSubject());
        Assert::assertStringContainsString('preheader text', $email);
        Assert::assertStringContainsString('admin@test-beta.mautibot.com', $message->getFrom()[0]->getAddress());
        Assert::assertStringContainsString('Admin', $message->getFrom()[0]->getName());
        Assert::assertStringNotContainsString('This should be overwritten by the form content', $email);
    }
}
