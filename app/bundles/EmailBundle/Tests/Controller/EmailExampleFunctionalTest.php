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
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Swift_Events_EventListener;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Symfony\Component\HttpFoundation\Request;

class EmailExampleFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['mailer_spool_type'] = 'file';

        parent::setUp();
    }

    public function testSendEmail(): void
    {
        $this->container->set('swiftmailer.transport.real', $transport = $this->createTransportFake());

        $lead  = $this->createLead();
        $email = $this->createEmail();
        $this->em->flush();

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

        self::assertCount(1, $transport->messages);

        $message = $transport->messages[0];

        // Asserting email data
        self::assertInstanceOf('Swift_Message', $message);
        self::assertSame('admin@yoursite.com', key($message->getTo()));
        self::assertContains('Email subject', $message->getSubject());
        self::assertContains(
            'Contact emails is test@domain.tld',
            $message->getBody()
        );
    }

    private function createEmail(): Email
    {
        $email = new Email();
        $email->setDateAdded(new DateTime());
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
