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
    private const PREHEADER_TEXT = 'Preheader text';

    protected $useCleanupRollback = false;

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
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo, self::PREHEADER_TEXT);

        $this->loginUser('admin');

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo, self::PREHEADER_TEXT);
    }

    private function assertPageContent(string $url, string ...$expectedContents): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        foreach ($expectedContents as $expectedContent) {
            self::assertStringContainsString($expectedContent, $crawler->text());
        }
    }

    private function createEmail(bool $publicPreview = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setPublicPreview($publicPreview);
        $email->setCustomHtml('<html><body>Contact emails is {contactfield=email}</body></html>');
        $email->setPreheaderText(self::PREHEADER_TEXT);
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
        $this->addLeadToSegment($lead, $segment1);
        $email = $this->createEmail();

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
        $email->setCustomHtml('<html><body>{dynamiccontent="Dynamic Content 1"}</body></html>');
        $this->em->persist($email);
        $this->em->flush();

        $url                    = "/email/preview/{$email->getId()}";
        $urlWithContact         = "{$url}?contactId={$lead->getId()}";
        $contentNoContactInfo   = 'Default Dynamic Content';
        $contentWithContactInfo = 'Variation 1';

        // Anonymous visitor
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentNoContactInfo, self::PREHEADER_TEXT);

        $this->loginUser('admin');

        // Admin user
        $this->assertPageContent($url, $contentNoContactInfo, self::PREHEADER_TEXT);
        $this->assertPageContent($urlWithContact, $contentWithContactInfo, self::PREHEADER_TEXT);
    }

    public function testPreviewEmailForDynamicContentVariantsWithCustomField(): void
    {
        // Create custom field
        $this->client->request(
            'POST',
            '/api/fields/contact/new',
            [
                'label'      => 'bool',
                'type'       => 'boolean',
                'properties' => [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ],
            ]
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode());
        self::assertJson($this->client->getResponse()->getContent());

        // Create some contacts
        $this->client->request(
            'POST',
            '/api/contacts/batch/new',
            [
                [
                    'firstname' => 'John',
                    'lastname'  => 'A',
                    'email'     => 'john.a@email.com',
                    'bool'      => true,
                ],
                [
                    'firstname' => 'John',
                    'lastname'  => 'B',
                    'email'     => 'john.b@email.com',
                    'bool'      => false,
                ],
                [
                    'firstname' => 'John',
                    'lastname'  => 'C',
                    'email'     => 'john.c@email.com',
                    'bool'      => null,
                ],
            ]
        );
        self::assertSame(
            201,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        // Create email with dynamic content variant
        $email          = $this->createEmail();
        $dynamicContent = [
            [
                'tokenName' => 'Dynamic Content 1',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => null,
                        'filters' => [],
                    ],
                ],
            ],
            [
                'tokenName' => 'Dynamic Content 2',
                'content'   => '<p>Default Dynamic Content</p>',
                'filters'   => [
                    [
                        'content' => '<p>Variant 1 Dynamic Content</p>',
                        'filters' => [
                            [
                                'glue'     => 'and',
                                'field'    => 'bool',
                                'object'   => 'lead',
                                'type'     => 'boolean',
                                'filter'   => '1',
                                'display'  => null,
                                'operator' => '=',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $email->setCustomHtml('<html><body><div>{dynamiccontent="Dynamic Content 2"}</div></body></html>');
        $email->setDynamicContent($dynamicContent);
        $this->em->flush();

        $url            = "/email/preview/{$email->getId()}";
        $defaultContent = 'Default Dynamic Content';
        $variantContent = 'Variant 1 Dynamic Content';

        // Non admin user - show default content
        $this->assertPageContent($url, $defaultContent);

        // Non admin user with contact preview - show default content
        $urlWithContact1 = "{$url}?contactId={$contacts['contacts'][0]['id']}";
        $this->assertPageContent($urlWithContact1, $defaultContent);

        // Login admin user
        $this->loginUser('admin');

        // Admin user with contact preview - show variant content - true filter matches
        $urlWithContact1 = "{$url}?contactId={$contacts['contacts'][0]['id']}";
        $this->assertPageContent($urlWithContact1, $variantContent);

        // Admin user with contact preview - show variant content - false filter doesn't matches
        $urlWithContact2 = "{$url}?contactId={$contacts['contacts'][1]['id']}";
        $this->assertPageContent($urlWithContact2, $defaultContent);

        // Admin user with contact preview - show variant content - null filter doesn't matches
        $urlWithContact3 = "{$url}?contactId={$contacts['contacts'][2]['id']}";
        $this->assertPageContent($urlWithContact3, $defaultContent);
    }

    public function testPreviewEmailWithInvalidIdThrows404Error(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/email/preview/5009');
        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('404 Not Found - Requested URL not found: /email/preview/5009', $crawler->text());
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
