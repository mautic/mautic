<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use DateTime;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Symfony\Component\HttpFoundation\Request;

class EmailExampleFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var Swift_Transport
     */
    private $transport;

    protected function setUp(): void
    {
        $this->configParams['mailer_spool_type'] = 'file';
        parent::setUp();

        self::$container->set('swiftmailer.mailer.default.transport.real', $this->transport = $this->createTransportFake());
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MappingException
     */
    public function testSendExampleEmailWithContact(): void
    {
        $lead  = $this->createLead();
        $email = $this->createEmail();
        $this->em->flush();
        $this->em->clear();

        $crawler     = $this->client->request(Request::METHOD_GET, "/s/emails/sendExample/{$email->getId()}");
        $formCrawler = $crawler->filter('form[name=example_send]');
        self::assertSame(1, $formCrawler->count());
        $form = $formCrawler->form();
        $form->setValues([
            'example_send[emails][list][0]' => 'admin@yoursite.com',
            'example_send[contact]'         => 'somebody',
            'example_send[contact_id]'      => $lead->getId(),
        ]);
        $this->client->submit($form);

        self::assertCount(1, $this->transport->messages);

        $message = $this->transport->messages[0];

        // Asserting email data
        self::assertInstanceOf('Swift_Message', $message);
        self::assertSame('admin@yoursite.com', key($message->getTo()));
        self::assertStringContainsString('Email subject for Test Lead, living in Lane 11, Near Post Office, Pune, India. Contact number: 012', $message->getSubject());
        self::assertStringContainsString('Email body for Test Lead, living in Lane 11, Near Post Office, Pune, India. Contact number: 012', $message->getBody());
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSendExampleEmailWithOutContact(): void
    {
        $email = $this->createEmail();
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
        self::assertStringContainsString('Contact emails is [Email]', $message->getBody());
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
        $email->setDateAdded(new DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject for {contactfield=firstname} {contactfield=lastname}, living in {contactfield=address1}, {contactfield=address2}, {contactfield=city}, {contactfield=country}. Contact number: {contactfield=mobile}');
        $email->setTemplate('Blank');
        $email->setCustomHtml('Email body for {contactfield=firstname} {contactfield=lastname}, living in {contactfield=address1}, {contactfield=address2}, {contactfield=city}, {contactfield=country}. Contact number: {contactfield=mobile}');
        $this->em->persist($email);

        return $email;
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $lead->setLastname('Lead');
        $lead->setMobile('012');
        $lead->setAddress1('Lane 11');
        $lead->setAddress2('Near Post Office');
        $lead->setCity('Pune');
        $lead->setCountry('India');
        $lead->setEmail('test@domain.tld');
        $this->em->persist($lead);

        return $lead;
    }

    private function createTransportFake(): Swift_Transport
    {
        return new class() implements Swift_Transport {
            /**
             * @var array
             */
            public $messages = [];

            public function isStarted(): bool
            {
                return true;
            }

            public function start(): void
            {
            }

            public function stop(): void
            {
            }

            public function ping(): bool
            {
                return true;
            }

            public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
            {
                $this->messages[] = clone $message;

                return count((array) $message->getTo())
                    + count((array) $message->getCc())
                    + count((array) $message->getBcc());
            }

            public function registerPlugin(Swift_Events_EventListener $plugin): void
            {
            }
        };
    }
}
