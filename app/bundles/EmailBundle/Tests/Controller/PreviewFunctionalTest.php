<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewFunctionalTest extends MauticMysqlTestCase
{
    public function testPreviewPage(): void
    {
        $lead  = $this->createLead();
        $email = $this->createEmail();
        $this->em->flush();

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Contact emails is [Email]';
        $contentWithContactInfo = sprintf('Contact emails is %s', $lead->getEmail());

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo);

        $this->loginUser('admin');

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo);
    }

    private function assertPageContent(string $url, string $expectedContent): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString($expectedContent, $crawler->text());
    }

    private function createEmail(bool $publicPreview = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setPublicPreview($publicPreview);
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
