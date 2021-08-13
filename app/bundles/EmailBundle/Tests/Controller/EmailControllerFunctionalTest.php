<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpFoundation\Request;

final class EmailControllerFunctionalTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        $this->clientOptions = ['debug' => true];

        parent::setUp();
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
        $prefix      = self::$container->getParameter('mautic.db_table_prefix');

        $dncQueries = array_filter(
            $queries['default'],
            function (array $query) use ($prefix) {
                return "SELECT l.id, dnc.reason FROM {$prefix}lead_donotcontact dnc LEFT JOIN {$prefix}leads l ON l.id = dnc.lead_id WHERE dnc.channel = :channel" === $query['sql'];
            }
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
        $prefix      = self::$container->getParameter('mautic.db_table_prefix');

        $dncQueries = array_filter(
            $queries['default'],
            function (array $query) use ($prefix, $contact) {
                return "SELECT l.id, dnc.reason FROM {$prefix}lead_donotcontact dnc LEFT JOIN {$prefix}leads l ON l.id = dnc.lead_id WHERE (dnc.channel = :channel) AND (l.id IN ({$contact->getId()}))" === $query['sql'];
            }
        );

        Assert::assertCount(1, $dncQueries, 'DNC query not found. '.var_export($queries, true));
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
        $form['emailform[isPublished]']->setValue(1);

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
        $form['emailform[variantSettings][weight]']->setValue($varientSetting['totalWeight']);
        $form['emailform[variantSettings][winnerCriteria]']->setValue($varientSetting['winnerCriteria']);
        $form['emailform[isPublished]']->setValue(1);

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

    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, ?LeadList $segment = null, ?array $varientSetting = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setVariantSettings($varientSetting);
        $email->addList($segment);
        $this->em->persist($email);

        return $email;
    }
}
