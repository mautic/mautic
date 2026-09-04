<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class EmailExampleFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected $useCleanupRollback = false;

    public function testSendExampleEmailWithContact(): void
    {
        $company = $this->createCompany('Mautic', 'hello@mautic.org');
        $company->setCity('Pune');
        $company->setCountry('India');

        $this->em->persist($company);

        $lead  = $this->createLead('John', 'Doe', 'test@domain.tld');
        $this->createPrimaryCompanyForLead($lead, $company);

        $email = $this->createEmail();
        $email->setCustomHtml('Contact emails is {contactfield=email}. Company details: {contactfield=companyname}, {contactfield=companycity}.');

        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => 'somebody',
            'example_send[contact_id]'      => $lead->getId(),
        ]);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Contact emails is test@domain.tld. Company details: Mautic, Pune.', $message->getBody()->toString());
    }

    public function testSendExampleEmailWithOutContact(): void
    {
        $email = $this->createEmail();
        $email->setCustomHtml('Contact emails is {contactfield=email}. Company details: {contactfield=companyname}, {contactfield=companycity}.');
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Contact emails is [Email]. Company details: [Company Name], [City].', $message->getBody()->toString());
    }

    public function testSendExampleEmailWithOutContactAndMonitoringEnabled(): void
    {
        $configParams = $this->configParams;

        // Set values, so \Mautic\EmailBundle\MonitoredEmail\Mailbox::isConfigured will return true.
        // Settings are not deep merged, so test is forced to include all other keys rather than just "general"
        $monitoredSettings = [
            'general' => [
                'host'     => 'some',
                'port'     => '993',
                'user'     => 'user',
                'password' => 'pwd',
            ],
            'EmailBundle_bounces' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'INBOX',
            ],
            'EmailBundle_unsubscribes' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => null,
            ],
            'EmailBundle_replies' => [
                'address'           => null,
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => null,
            ],
        ];

        $configParams['monitored_email'] = $monitoredSettings;

        $this->setUpSymfony($configParams);

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $this->clientServer['PHP_AUTH_USER'] ?? 'admin']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $email = $this->createEmail();
        $email->setCustomHtml('Contact emails is {contactfield=email}. Company details: {contactfield=companyname}, {contactfield=companycity}.');
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(Response::HTTP_OK, verbose: true);

        $message = self::getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Contact emails is [Email]. Company details: [Company Name], [City].', $message->getBody()->toString());
    }

    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithNoContact(): void
    {
        // Create custom field
        $this->client->request(
            'POST',
            '/api/fields/contact/new',
            [
                'label'      => 'bool',
                'type'       => 'boolean',
                'order'      => $this->getOrder(), // Race-condition in PostgreSQL require order to be added.
                'properties' => [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ],
            ]
        );
        $response = $this->client->getResponse()->getContent();
        self::assertResponseStatusCodeSame(201, $response);
        $this->assertJson($response);

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
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues(['example_send[emails][list][0]' => 'admin@yoursite.com']);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Default Dynamic Content', $message->getBody()->toString());
    }

    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithMatchFilterContact(): void
    {
        // Create custom field
        $this->client->request(
            'POST',
            '/api/fields/contact/new',
            [
                'label'      => 'bool',
                'type'       => 'boolean',
                'order'      => $this->getOrder(),
                'properties' => [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ],
            ]
        );
        $response = $this->client->getResponse()->getContent();
        self::assertResponseStatusCodeSame(201, $response);
        $this->assertJson($response);

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
        self::assertResponseStatusCodeSame(201, $this->client->getResponse()->getContent());
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => $contacts['contacts'][0]['fields']['core']['firstname']['value'],
            'example_send[contact_id]'      => $contacts['contacts'][0]['id'],
        ]);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Variant 1 Dynamic Content', $message->getBody()->toString());
    }

    public function testSendExampleEmailForDynamicContentVariantsWithCustomFieldWithNoMatchFilterContact(): void
    {
        // Create custom field
        $this->client->request(
            'POST',
            '/api/fields/contact/new',
            [
                'label'      => 'bool',
                'type'       => 'boolean',
                'order'      => $this->getOrder(),
                'properties' => [
                    'no'  => 'No',
                    'yes' => 'Yes',
                ],
            ]
        );
        $response = $this->client->getResponse()->getContent();
        self::assertResponseStatusCodeSame(201, $response);
        $this->assertJson($response);

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
        self::assertResponseStatusCodeSame(201, $this->client->getResponse()->getContent());
        $contacts = json_decode($this->client->getResponse()->getContent(), true);

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => $contacts['contacts'][0]['fields']['core']['firstname']['value'],
            'example_send[contact_id]'      => $contacts['contacts'][0]['id'],
        ]);
        $this->client->submit($form);

        $message = $this->getMailerMessagesByToAddress('admin@yoursite.com')[0];
        $this->assertInstanceOf(MauticMessage::class, $message);

        $this->assertSame('[TEST] [TEST] Email subject', $message->getSubject());
        $this->assertStringContainsString('Default Dynamic Content', $message->getBody()->toString());
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

    private function getOrder(string $group = 'core', string $object = 'lead'): ?int
    {
        $orderField = $this->em->getRepository(\Mautic\LeadBundle\Entity\LeadField::class)
            ->findOneBy(['group' => $group, 'object' => $object, 'isFixed' => false], ['order' => 'DESC']);

        return $orderField ? $orderField->getId() : null;
    }
}
