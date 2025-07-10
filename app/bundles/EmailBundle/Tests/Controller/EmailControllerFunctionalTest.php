<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerFunctionalTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    public function setUp(): void
    {
        $this->configParams['disable_trackable_urls'] = false;
        $this->configParams['mailer_from_name']       = 'Mautic Admin';
        $this->configParams['mailer_from_email']      = 'admin@email.com';
        $this->configParams['mailer_custom_headers']  = ['x-global-custom-header' => 'value123'];
        $this->clientOptions                          = ['debug' => true];

        parent::setUp();
    }

    /**
     * Check if email contains correct values.
     */
    public function testViewEmail(): void
    {
        $email = $this->createEmail('ABC', 'template', 'list', 'blank', 'Test html');
        $email->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $email->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $email->setCreatedByUser('Test User');

        $this->em->persist($email);
        $this->em->flush();
        $this->em->detach($email);

        $this->client->request('GET', '/s/emails');
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200');
        $this->assertStringContainsString('February 7, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('March 21, 2020', $clientResponse->getContent());
        $this->assertStringContainsString('Test User', $clientResponse->getContent());

        $urlAlias   = 'emails';
        $routeAlias = 'email';
        $column     = 'dateModified';
        $column2    = 'name';
        $tableAlias = 'e.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    /**
     * Filtering should return status code 200.
     */
    public function testIndexActionWhenFiltering(): void
    {
        $this->client->request('GET', '/s/emails?search=has%3Aresults&tmpl=list');
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful('Return code must be 200.');
    }

    /**
     * Ensure there is no query for DNC reasons if there are no contacts who received the email
     * because it loads the whole DNC table if no contact IDs are provided. It can lead to
     * memory limit error if the DNC table is big.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testProfileEmailDetailPageForUnsentEmail(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Email A Subject', 'list', 'blank', 'Test html', $segment);
        $this->em->flush();

        $this->client->enableProfiler();
        $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");

        $profile = $this->client->getProfile();

        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector = $profile->getCollector('db');
        $queries     = $dbCollector->getQueries();
        $prefix      = static::getContainer()->getParameter('mautic.db_table_prefix');

        $dncQueries = array_filter(
            $queries['default'],
            fn (array $query) => "SELECT l.id, dnc.reason FROM {$prefix}lead_donotcontact dnc LEFT JOIN {$prefix}leads l ON l.id = dnc.lead_id WHERE dnc.channel = :channel" === $query['sql']
        );

        Assert::assertCount(0, $dncQueries);
    }

    /**
     * On the other hand there should be the query for DNC reasons if there are contacts who received the email.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testProfileEmailDetailPageForSentEmail(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Email A Subject', 'list', 'blank', 'Test html', $segment);

        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $this->em->persist($contact);
        $this->em->persist($emailStat);
        $this->em->flush();

        $this->client->enableProfiler();
        $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");

        $profile = $this->client->getProfile();

        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector = $profile->getCollector('db');
        $queries     = $dbCollector->getQueries();
        $prefix      = static::getContainer()->getParameter('mautic.db_table_prefix');

        $dncQueries = array_filter(
            $queries['default'],
            fn (array $query) => "SELECT l.id, dnc.reason FROM {$prefix}lead_donotcontact dnc LEFT JOIN {$prefix}leads l ON l.id = dnc.lead_id WHERE (dnc.channel = ?) AND (l.id IN ({$contact->getId()}))" === $query['sql']
        );

        Assert::assertCount(1, $dncQueries, 'DNC query not found. '.var_export(array_map(fn (array $query) => $query['sql'], $queries['default']), true));
    }

    public function testEmailDetailPageForDisabledSendButton(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Subject A', 'list', 'blank', 'test html', $segment);
        $email->setPublishUp(new \DateTime('now -1 hour'));
        $this->em->persist($email);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");
        $html    = $crawler->filterXPath('//*[@id="toolbar"]/div[1]/a[2]')->html();
        $this->assertStringContainsString('Email is sending in the background', $html, $html);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $html    = $crawler->filter('.email-list > tbody > tr:nth-child(1) > td:nth-child(1)')->html();
        $this->assertStringContainsString('Email is sending in the background', $html, $html);

        $email->setPublishUp(new \DateTime('now +1 hour'));
        $this->em->persist($email);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");
        $html    = $crawler->filterXPath('//*[@id="toolbar"]/div[1]/a[2]')->html();
        $this->assertStringNotContainsString('Email is sending in the background', $html, $html);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails');
        $html    = $crawler->filter('.email-list > tbody > tr:nth-child(1) > td:nth-child(1)')->html();
        $this->assertStringNotContainsString('Email is sending in the background', $html, $html);

        $email->setPublishUp(null);
        $this->em->persist($email);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");
        $html    = $crawler->filterXPath('//*[@id="toolbar"]/div[1]/a[2]')->html();
        $this->assertStringNotContainsString('disabled', $html, $html);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testSegmentEmailTranslationLookUp(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Email A Subject', 'list', 'blank', 'Test html', $segment);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $html    = $crawler->filterXPath("//select[@id='emailform_segmentTranslationParent']//optgroup")->html();
        self::assertSame('<option value="'.$email->getId().'">'.$email->getName().'</option>', trim($html));
    }

    public function testSegmentEmailSend(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Subject A', 'list', 'blank', 'Ahoy <i>{contactfield=email}</i><a href="https://mautic.org">Mautic</a>', $segment);

        foreach (['contact@one.email', 'contact@two.email'] as $emailAddress) {
            $contact = new Lead();
            $contact->setEmail($emailAddress);

            $member = new ListLead();
            $member->setLead($contact);
            $member->setList($segment);
            $member->setDateAdded(new \DateTime());

            $this->em->persist($member);
            $this->em->persist($contact);
        }
        $this->em->flush();

        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', [
            'id'         => $email->getId(),
            'pending'    => 2,
            'batchLimit' => 10,
        ]);

        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertSame('{"success":1,"percent":100,"progress":[2,2],"stats":{"sent":2,"failed":0,"failedRecipients":[]}}', $this->client->getResponse()->getContent());
        $this->assertQueuedEmailCount(2);

        $email = $this->getMailerMessage();

        // The order of the recipients is not guaranteed, so we need to check both possibilities.
        Assert::assertSame('Subject A', $email->getSubject());
        Assert::assertMatchesRegularExpression('#Ahoy <i>contact@(one|two)\.email<\/i><a href="https:\/\/localhost\/r\/[a-z0-9]+\?ct=[a-zA-Z0-9%]+">Mautic<\/a><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email->getHtmlBody());
        Assert::assertMatchesRegularExpression('#Ahoy _contact@(one|two).email_#', $email->getTextBody()); // Are the underscores expected?
        Assert::assertCount(1, $email->getFrom());
        Assert::assertSame($this->configParams['mailer_from_name'], $email->getFrom()[0]->getName());
        Assert::assertSame($this->configParams['mailer_from_email'], $email->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email->getTo());
        Assert::assertSame('', $email->getTo()[0]->getName());
        Assert::assertMatchesRegularExpression('#contact@(one|two).email#', $email->getTo()[0]->getAddress());
        Assert::assertCount(1, $email->getReplyTo());
        Assert::assertSame('', $email->getReplyTo()[0]->getName());
        Assert::assertSame($this->configParams['mailer_from_email'], $email->getReplyTo()[0]->getAddress());
        Assert::assertSame('value123', $email->getHeaders()->get('x-global-custom-header')->getBody());
    }

    public function testSegmentEmailSendWithAdvancedOptions(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Subject A', 'list', 'blank', 'Ahoy <i>{contactfield=email}</i><a href="https://mautic.org">Mautic</a>', $segment);
        $email->setPlainText('Dear {contactfield=email}');
        $email->setFromAddress('custom@from.address');
        $email->setFromName('Custom From Name');
        $email->setReplyToAddress('custom@replyto.address');
        $email->setBccAddress('custom@bcc.address');
        $email->setHeaders(['x-global-custom-header' => 'value123 overridden']);
        $email->setUtmTags(
            [
                'utmSource'   => 'utmSourceA',
                'utmMedium'   => 'utmMediumA',
                'utmCampaign' => 'utmCampaignA',
                'utmContent'  => 'utmContentA',
            ]
        );

        foreach (['contact@one.email', 'contact@two.email'] as $emailAddress) {
            $contact = new Lead();
            $contact->setEmail($emailAddress);

            $member = new ListLead();
            $member->setLead($contact);
            $member->setList($segment);
            $member->setDateAdded(new \DateTime());

            $this->em->persist($member);
            $this->em->persist($contact);
        }

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->flush();

        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', [
            'id'         => $email->getId(),
            'pending'    => 2,
            'batchLimit' => 10,
        ]);

        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertSame('{"success":1,"percent":100,"progress":[2,2],"stats":{"sent":2,"failed":0,"failedRecipients":[]}}', $this->client->getResponse()->getContent());
        $this->assertQueuedEmailCount(2);

        $email = $this->getMailerMessage();

        // The order of the recipients is not guaranteed, so we need to check both possibilities.
        Assert::assertSame('Subject A', $email->getSubject());
        Assert::assertMatchesRegularExpression('#Ahoy <i>contact@(one|two)\.email<\/i><a href="https:\/\/localhost\/r\/[a-z0-9]+\?ct=[a-zA-Z0-9%]+&utm_source=utmSourceA&utm_medium=utmMediumA&utm_campaign=utmCampaignA&utm_content=utmContentA">Mautic<\/a><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email->getHtmlBody());
        Assert::assertMatchesRegularExpression('#Dear contact@(one|two).email#', $email->getTextBody());
        Assert::assertCount(1, $email->getFrom());
        Assert::assertSame('Custom From Name', $email->getFrom()[0]->getName());
        Assert::assertSame('custom@from.address', $email->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email->getTo());
        Assert::assertSame('', $email->getTo()[0]->getName());
        Assert::assertMatchesRegularExpression('#contact@(one|two).email#', $email->getTo()[0]->getAddress());
        Assert::assertCount(1, $email->getReplyTo());
        Assert::assertSame('', $email->getReplyTo()[0]->getName());
        Assert::assertSame('custom@replyto.address', $email->getReplyTo()[0]->getAddress());
        Assert::assertSame('value123', $email->getHeaders()->get('x-global-custom-header')->getBody());
    }

    public function testSegmentEmailSendWithTokenInFromAddress(): void
    {
        $segment = $this->createSegment('Segment A', 'segment-a');
        $email   = $this->createEmail('Email A', 'Subject A', 'list', 'blank', 'Ahoy <i>{contactfield=email}</i><a href="https://mautic.org">Mautic</a>', $segment);
        $email->setPlainText('Dear {contactfield=email}');
        $email->setFromAddress('{contactfield=address2}');
        $email->setFromName('{contactfield=address1}');
        $email->setReplyToAddress('custom@replyto.address');

        foreach (['contact@one.email', 'contact@two.email'] as $emailAddress) {
            $contact = new Lead();
            $contact->setEmail($emailAddress);
            $contact->setAddress1('address1 name for '.$emailAddress);
            $contact->setAddress2('address2+'.$emailAddress);

            $member = new ListLead();
            $member->setLead($contact);
            $member->setList($segment);
            $member->setDateAdded(new \DateTime());

            $this->em->persist($member);
            $this->em->persist($contact);
        }

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->flush();

        $this->setCsrfHeader();
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=email:sendBatch', [
            'id'         => $email->getId(),
            'pending'    => 2,
            'batchLimit' => 10,
        ]);

        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        $this->assertSame('{"success":1,"percent":100,"progress":[2,2],"stats":{"sent":2,"failed":0,"failedRecipients":[]}}', $this->client->getResponse()->getContent());
        $this->assertQueuedEmailCount(2);

        $messages   = self::getMailerMessages();
        $messageOne = array_values(array_filter($messages, fn ($message) => 'contact@one.email' === $message->getTo()[0]->getAddress()))[0];
        $messageTwo = array_values(array_filter($messages, fn ($message) => 'contact@two.email' === $message->getTo()[0]->getAddress()))[0];

        Assert::assertSame('Subject A', $messageOne->getSubject());
        Assert::assertMatchesRegularExpression('#Ahoy <i>contact@one\.email<\/i><a href="https:\/\/localhost\/r\/[a-z0-9]+\?ct=[a-zA-Z0-9%]+">Mautic<\/a><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $messageOne->getHtmlBody());
        Assert::assertSame('Dear contact@one.email', $messageOne->getTextBody());
        Assert::assertCount(1, $messageOne->getFrom());
        Assert::assertSame('address1 name for contact@one.email', $messageOne->getFrom()[0]->getName());
        Assert::assertSame('address2+contact@one.email', $messageOne->getFrom()[0]->getAddress());
        Assert::assertCount(1, $messageOne->getTo());
        Assert::assertSame('', $messageOne->getTo()[0]->getName());
        Assert::assertSame('contact@one.email', $messageOne->getTo()[0]->getAddress());
        Assert::assertCount(1, $messageOne->getReplyTo());
        Assert::assertSame('', $messageOne->getReplyTo()[0]->getName());
        Assert::assertSame('custom@replyto.address', $messageOne->getReplyTo()[0]->getAddress());
        Assert::assertSame('value123', $messageOne->getHeaders()->get('x-global-custom-header')->getBody());

        Assert::assertSame('Subject A', $messageTwo->getSubject());
        Assert::assertMatchesRegularExpression('#Ahoy <i>contact@two\.email<\/i><a href="https:\/\/localhost\/r\/[a-z0-9]+\?ct=[a-zA-Z0-9%]+">Mautic<\/a><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $messageTwo->getHtmlBody());
        Assert::assertSame('Dear contact@two.email', $messageTwo->getTextBody());
        Assert::assertCount(1, $messageTwo->getFrom());
        Assert::assertSame('address1 name for contact@two.email', $messageTwo->getFrom()[0]->getName());
        Assert::assertSame('address2+contact@two.email', $messageTwo->getFrom()[0]->getAddress());
        Assert::assertCount(1, $messageTwo->getTo());
        Assert::assertSame('', $messageTwo->getTo()[0]->getName());
        Assert::assertSame('contact@two.email', $messageTwo->getTo()[0]->getAddress());
        Assert::assertCount(1, $messageTwo->getReplyTo());
        Assert::assertSame('', $messageTwo->getReplyTo()[0]->getName());
        Assert::assertSame('custom@replyto.address', $messageTwo->getReplyTo()[0]->getAddress());
        Assert::assertSame('value123', $messageTwo->getHeaders()->get('x-global-custom-header')->getBody());
    }

    public function testCloneAction(): void
    {
        $segment = $this->createSegment('Segment B', 'segment-B');
        $email   = $this->createEmail('Email B', 'Email B Subject', 'list', 'blank', 'Test html', $segment);
        $this->em->flush();

        // request for email clone
        $crawler        = $this->client->request(Request::METHOD_GET, "/s/emails/clone/{$email->getId()}");
        $buttonCrawler  =  $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form['emailform[emailType]']->setValue('list');
        $form['emailform[subject]']->setValue('Email B Subject clone');
        $form['emailform[name]']->setValue('Email B clone');
        $form['emailform[isPublished]']->setValue('1');

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $emails = $this->em->getRepository(Email::class)->findBy([], ['id' => 'ASC']);
        Assert::assertCount(2, $emails);

        $firstEmail  = $emails[0];
        $secondEmail = $emails[1];

        Assert::assertSame($email->getId(), $firstEmail->getId());
        Assert::assertNotSame($email->getId(), $secondEmail->getId());
        Assert::assertEquals('list', $secondEmail->getEmailType());
        Assert::assertEquals('Email B Subject', $firstEmail->getSubject());
        Assert::assertEquals('Email B', $firstEmail->getName());
        Assert::assertEquals('Email B Subject clone', $secondEmail->getSubject());
        Assert::assertEquals('Email B clone', $secondEmail->getName());
        Assert::assertEquals('Test html', $secondEmail->getCustomHtml());
    }

    public function testAbTestAction(): void
    {
        $segment        = $this->createSegment('Segment B', 'segment-B');
        $varientSetting = ['totalWeight' => 100, 'winnerCriteria' => 'email.openrate'];
        $email          = $this->createEmail('Email B', 'Email B Subject', 'list', 'blank', 'Test html', $segment, $varientSetting);
        $this->em->flush();

        // request for email clone
        $crawler        = $this->client->request(Request::METHOD_GET, "/s/emails/abtest/{$email->getId()}");
        $buttonCrawler  =  $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form['emailform[subject]']->setValue('Email B Subject var 2');
        $form['emailform[name]']->setValue('Email B var 2');
        $form['emailform[variantSettings][weight]']->setValue((string) $varientSetting['totalWeight']);
        $form['emailform[variantSettings][winnerCriteria]']->setValue($varientSetting['winnerCriteria']);
        $form['emailform[isPublished]']->setValue('1');

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $emails = $this->em->getRepository(Email::class)->findBy([], ['id' => 'ASC']);
        Assert::assertCount(2, $emails);

        $firstEmail  = $emails[0];
        $secondEmail = $emails[1];

        Assert::assertSame($email->getId(), $firstEmail->getId());
        Assert::assertNotSame($email->getId(), $secondEmail->getId());
        Assert::assertEquals('list', $secondEmail->getEmailType());
        Assert::assertEquals('Email B Subject', $firstEmail->getSubject());
        Assert::assertEquals('Email B', $firstEmail->getName());
        Assert::assertEquals('Email B Subject var 2', $secondEmail->getSubject());
        Assert::assertEquals('Email B var 2', $secondEmail->getName());
        Assert::assertEquals('blank', $secondEmail->getTemplate());
        Assert::assertEquals('Test html', $secondEmail->getCustomHtml());
        Assert::assertEquals($firstEmail->getId(), $secondEmail->getVariantParent()->getId());
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setAlias($alias);
        $segment->setPublicName($name);
        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @param mixed[]|null $varientSetting
     */
    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, LeadList $segment = null, ?array $varientSetting = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setVariantSettings($varientSetting);
        if (!empty($segment)) {
            $email->addList($segment);
        }
        $this->em->persist($email);

        return $email;
    }

    public function testEmailDetailsPageShouldNotHavePendingCount(): void
    {
        // Create a segment
        $segment = new LeadList();
        $segment->setName('Test Segment A');
        $segment->setPublicName('Test Segment A');
        $segment->setAlias('test-segment-a');

        // Create email template of type "list" and attach the segment to it
        $email = new Email();
        $email->setName('Test Email C');
        $email->setSubject('Test Email C Subject');
        $email->setEmailType('list');
        $email->setCustomHtml('This is Email C custom HTML.');
        $email->addList($segment);

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->flush();

        $this->client->enableProfiler();
        $crawler = $this->client->request(Request::METHOD_GET, "/s/emails/view/{$email->getId()}");

        // checking if pending count is removed from details page ui
        $emailDetailsContainer = trim($crawler->filter('#email-details')->filter('tbody')->text(null, false));
        $this->assertStringNotContainsString('Pending', $emailDetailsContainer);

        $profile = $this->client->getProfile();

        /** @var DoctrineDataCollector $dbCollector */
        $dbCollector = $profile->getCollector('db');
        $queries     = $dbCollector->getQueries();
        $prefix      = static::getContainer()->getParameter('mautic.db_table_prefix');

        $pendingCountQuery = array_filter(
            $queries['default'],
            fn (array $query) => $query['sql'] === "SELECT count(*) as count FROM {$prefix}leads l WHERE (EXISTS (SELECT null FROM {$prefix}lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN ({$segment->getId()})) AND (ll.manually_removed = :false))) AND (NOT EXISTS (SELECT null FROM {$prefix}lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (NOT EXISTS (SELECT null FROM {$prefix}email_stats stat WHERE (stat.lead_id = l.id) AND (stat.email_id IN ({$email->getId()})))) AND (NOT EXISTS (SELECT null FROM {$prefix}message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id IN ({$email->getId()})))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"
        );

        $this->assertCount(0, $pendingCountQuery);
    }

    public function testSendEmailForImportCustomEmailTemplate(): void
    {
        $email = new Email();
        $email->setName('Test Email C');
        $email->setSubject('Test Email C Subject');
        $email->setTemplate('blank');
        $email->setEmailType('template');

        $contact = new Lead();
        $contact->setEmail('john@doe.email');

        $this->em->persist($email);
        $this->em->persist($contact);
        $this->em->flush();

        // Create the member now.
        $payload = [
            'action'   => 'lead:getEmailTemplate',
            'template' => $email->getId(),
        ];

        $this->client->xmlHttpRequest('GET', '/s/ajax', $payload);
        $clientResponse = $this->client->getResponse();

        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        $this->assertSame(1, $response['success']);
        $this->assertNotEmpty($response['subject']);
        $this->assertEquals($email->getSubject(), $response['subject']);
        $this->assertNotEmpty($response['body']);
    }

    /**
     * Test email name length validation (190 character limit).
     */
    public function testEmailNameLengthValidation(): void
    {
        $longName = str_repeat('a', Email::MAX_NAME_SUBJECT_LENGTH + 1); // 191 characters

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->selectButton('emailform[buttons][save]')->form();
        $form['emailform[name]']->setValue($longName);
        $form['emailform[subject]']->setValue('Valid Subject');
        $form['emailform[emailType]']->setValue('template');

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertStringContainsString('Email name maximum length is 190 characters', $response->getContent());
    }

    /**
     * Test email subject length validation (190 character limit).
     */
    public function testEmailSubjectLengthValidation(): void
    {
        $longSubject = str_repeat('b', Email::MAX_NAME_SUBJECT_LENGTH + 1); // 191 characters

        $crawler = $this->client->request(Request::METHOD_GET, '/s/emails/new');
        $this->assertTrue($this->client->getResponse()->isOk());

        $form = $crawler->selectButton('emailform[buttons][save]')->form();
        $form['emailform[name]']->setValue('Valid Name');
        $form['emailform[subject]']->setValue($longSubject);
        $form['emailform[emailType]']->setValue('template');

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertStringContainsString('Email subject maximum length is 190 characters', $response->getContent());
    }
}
