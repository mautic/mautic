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
     */
    public function testProfileEmailDetailPageForUnsentEmail()
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($segment);
        $this->em->persist($segment);
        $this->em->persist($email);
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
     */
    public function testProfileEmailDetailPageForSentEmail()
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($segment);
        $contact = new Lead();
        $contact->setEmail('john@doe.email');
        $emailStat = new Stat();
        $emailStat->setEmail($email);
        $emailStat->setLead($contact);
        $emailStat->setEmailAddress($contact->getEmail());
        $emailStat->setDateSent(new \DateTime());
        $this->em->persist($segment);
        $this->em->persist($email);
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
}
