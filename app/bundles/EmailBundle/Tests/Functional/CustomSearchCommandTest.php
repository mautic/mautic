<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Event\SearchCommandEvent;
use Mautic\CoreBundle\Event\SearchQueryEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CustomSearchCommandTest extends MauticMysqlTestCase
{
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
    }

    public function testCustomSearchCommandIsAddedToEmailSearchCommands(): void
    {
        $customCommandAdded = false;

        $listener = function (SearchCommandEvent $event) use (&$customCommandAdded): void {
            if ('email' === $event->getContext()) {
                $event->addCommand('mautic.test.custom.searchcommand');
                $customCommandAdded = true;
            }
        };

        $this->dispatcher->addListener(SearchCommandEvent::class, $listener);

        try {
            $emailModel = self::getContainer()->get(EmailModel::class);
            $commands   = $emailModel->getCommandList();

            $this->assertTrue($customCommandAdded, 'SearchCommandEvent listener was not called');
            $this->assertContains('mautic.test.custom.searchcommand', $commands, 'Custom command was not added to the list');
        } finally {
            $this->dispatcher->removeListener(SearchCommandEvent::class, $listener);
        }
    }

    public function testSearchCommandEventContextIsEmail(): void
    {
        $capturedContext = null;

        $listener = function (SearchCommandEvent $event) use (&$capturedContext): void {
            $capturedContext = $event->getContext();
        };

        $this->dispatcher->addListener(SearchCommandEvent::class, $listener);

        try {
            $emailModel = self::getContainer()->get(EmailModel::class);
            $emailModel->getCommandList();

            $this->assertSame('email', $capturedContext, 'SearchCommandEvent context should be "email"');
        } finally {
            $this->dispatcher->removeListener(SearchCommandEvent::class, $listener);
        }
    }

    public function testCustomSearchCommandFiltersEmails(): void
    {
        $email1 = $this->createEmail('Email One');
        $email1->setCustomHtml('<html><body>Special marker: FINDME</body></html>');

        $email2 = $this->createEmail('Email Two');
        $email2->setCustomHtml('<html><body>Regular content</body></html>');

        $this->em->flush();
        $this->em->clear();

        $listener = function (SearchCommandEvent $event): void {
            if ('email' === $event->getContext()) {
                $event->addCommand('is:special');
            }
        };

        $queryListener = function (SearchQueryEvent $event): void {
            if ('email' !== $event->getContext()) {
                return;
            }

            $filter = $event->getFilter();
            if ('is:special' !== $filter->command) {
                return;
            }

            $q     = $event->getQuery();
            $alias = $event->getAlias();

            $event->setExpr($q->expr()->like($alias.'.customHtml', ':specialMarker'));
            $event->setParameters(['specialMarker' => '%FINDME%']);
        };

        $this->dispatcher->addListener(SearchCommandEvent::class, $listener);
        $this->dispatcher->addListener(SearchQueryEvent::class, $queryListener);

        try {
            $this->client->request(Request::METHOD_GET, '/s/emails', ['search' => 'is:special']);
            $this->assertResponseIsSuccessful();

            $content = $this->client->getResponse()->getContent();
            $this->assertStringContainsString('Email One', (string) $content, 'Email with special marker should be found');
            $this->assertStringNotContainsString('Email Two', (string) $content, 'Email without special marker should not be found');
        } finally {
            $this->dispatcher->removeListener(SearchCommandEvent::class, $listener);
            $this->dispatcher->removeListener(SearchQueryEvent::class, $queryListener);
        }
    }

    private function createEmail(string $name): Email
    {
        $segment = new LeadList();
        $segment->setName('Segment for '.$name);
        $segment->setPublicName('Segment for '.$name);
        $segment->setAlias('segment-for-'.strtolower(str_replace(' ', '-', $name)));
        $this->em->persist($segment);

        $email = new Email();
        $email->setIsPublished(true);
        $email->setName($name);
        $email->setSubject($name);
        $email->setEmailType('list');
        $email->setCustomHtml('<html><body>Test</body></html>');
        $email->addList($segment);
        $this->em->persist($email);

        return $email;
    }
}
