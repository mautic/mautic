<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
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

    public function testPreviewEmailWithCorrectDCVariationFilterSegmentMembership(): void
    {
        $segment1 = $this->createSegment('Segment 1');
        $segment2 = $this->createSegment('Segment 2');
        $lead     = $this->createLead();
        $email    = $this->createEmail();

        $this->addLeadToSegment($lead, $segment1);

        $email->setDynamicContent([
            [
                'tokenName' => 'Dynamic Content 1',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => '<p>Variation 1</p>',
                        'filters' => [
                            [
                                'glue'   => 'and',
                                'field'  => 'leadlist',
                                'object' => 'lead',
                                'type'   => 'leadlist',
                                'filter' => [
                                    $segment1->getId(),
                                    $segment2->getId(),
                                ],
                                'display'  => 'Segment Membership',
                                'operator' => 'in',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $email->setCustomHtml('{dynamiccontent="Dynamic Content 1"}');
        $this->em->persist($email);
        $this->em->flush();

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Default Dynamic Content';
        $contentWithContactInfo = 'Variation 1';

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo);

        $this->loginUser('admin');

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo);
    }

    private function createSegment(string $name = 'Segment 1'): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower($name));
        $segment->isPublished(true);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }

    private function addLeadToSegment(Lead $lead, LeadList $segment): ListLead
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);
        $this->em->flush();

        return $listLead;
    }
}
