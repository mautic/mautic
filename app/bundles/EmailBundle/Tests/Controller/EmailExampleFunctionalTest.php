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

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithNoContact(): void
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
        $response = $this->client->getResponse()->getContent();
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), $response);
        self::assertJson($response);

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
        $email->setCustomHtml('<div>{dynamiccontent="Dynamic Content 2"}</div>');
        $email->setDynamicContent($dynamicContent);
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        self::assertSame(1, $formCrawler->count());
        $form = $formCrawler->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);
        self::assertCount(1, $this->transport->messages);
        $message = $this->transport->messages[0];

        // Asserting email data
        self::assertInstanceOf('Swift_Message', $message);
        self::assertSame('admin@yoursite.com', key($message->getTo()));
        self::assertStringContainsString('Email subject', $message->getSubject());
        self::assertStringContainsString('Default Dynamic Content', $message->getBody());
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithMatchFilterContact(): void
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
        $response = $this->client->getResponse()->getContent();
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), $response);
        self::assertJson($response);

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
        $email->setCustomHtml('<div>{dynamiccontent="Dynamic Content 2"}</div>');
        $email->setDynamicContent($dynamicContent);
        $this->em->flush();
        $this->em->clear();

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
            ]
        );
        self::assertSame(
            201,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        self::assertSame(1, $formCrawler->count());
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => $contacts['contacts'][0]['firstname'],
            'example_send[contact_id]'      => $contacts['contacts'][0]['id'],
        ]);
        $this->client->submit($form);
        self::assertCount(1, $this->transport->messages);
        $message = $this->transport->messages[0];

        // Asserting email data
        self::assertInstanceOf('Swift_Message', $message);
        self::assertSame('admin@yoursite.com', key($message->getTo()));
        self::assertStringContainsString('Email subject', $message->getSubject());
        self::assertStringContainsString('Variant 1 Dynamic Content', $message->getBody());
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithNoMatchFilterContact(): void
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
        $response = $this->client->getResponse()->getContent();
        self::assertSame(201, $this->client->getResponse()->getStatusCode(), $response);
        self::assertJson($response);

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
        $email->setCustomHtml('<div>{dynamiccontent="Dynamic Content 2"}</div>');
        $email->setDynamicContent($dynamicContent);
        $this->em->flush();
        $this->em->clear();

        // Create some contacts
        $this->client->request(
            'POST',
            '/api/contacts/batch/new',
            [
                [
                    'firstname' => 'John',
                    'lastname'  => 'A',
                    'email'     => 'john.a@email.com',
                    'bool'      => false,
                ],
            ]
        );
        self::assertSame(
            201,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        self::assertSame(1, $formCrawler->count());
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => $contacts['contacts'][0]['firstname'],
            'example_send[contact_id]'      => $contacts['contacts'][0]['id'],
        ]);
        $this->client->submit($form);
        self::assertCount(1, $this->transport->messages);
        $message = $this->transport->messages[0];

        // Asserting email data
        self::assertInstanceOf('Swift_Message', $message);
        self::assertSame('admin@yoursite.com', key($message->getTo()));
        self::assertStringContainsString('Email subject', $message->getSubject());
        self::assertStringContainsString('Default Dynamic Content', $message->getBody());
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
