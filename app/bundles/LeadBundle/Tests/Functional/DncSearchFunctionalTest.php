<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

final class DncSearchFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private const MESSAGE_EMAIL_DNC_SHOULD_APPEAR_IN_EMAIL_SEARCH = 'Contact with email DNC should appear in dnc:email search';

    public function testDncSearchWithAnyChannel(): void
    {
        $contact1 = $this->createContact('contact1@test.com');
        $contact2 = $this->createContact('contact2@test.com');
        $contact3 = $this->createContact('contact3@test.com');

        $this->addDncRecord($contact1->getId(), 'email');
        $this->addDncRecord($contact2->getId(), 'sms');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Aany');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringContainsString($contact1->getEmail(), $responseText, 'Contact with email DNC should appear in dnc:any search');
        $this->assertStringContainsString($contact2->getEmail(), $responseText, 'Contact with SMS DNC should appear in dnc:any search');
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without DNC should not appear in dnc:any search');
    }

    public function testDncSearchWithSpecificChannel(): void
    {
        $contact1 = $this->createContact('email-dnc@test.com');
        $contact2 = $this->createContact('sms-dnc@test.com');
        $contact3 = $this->createContact('no-dnc@test.com');

        $this->addDncRecord($contact1->getId(), 'email');
        $this->addDncRecord($contact2->getId(), 'sms');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Aemail');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringContainsString($contact1->getEmail(), $responseText, self::MESSAGE_EMAIL_DNC_SHOULD_APPEAR_IN_EMAIL_SEARCH);
        $this->assertStringNotContainsString($contact2->getEmail(), $responseText, 'Contact with SMS DNC should not appear in dnc:email search');
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without DNC should not appear in dnc:email search');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Asms');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringNotContainsString($contact1->getEmail(), $responseText, 'Contact with email DNC should not appear in dnc:sms search');
        $this->assertStringContainsString($contact2->getEmail(), $responseText, 'Contact with SMS DNC should appear in dnc:sms search');
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without DNC should not appear in dnc:sms search');
    }

    public function testDncSearchNegation(): void
    {
        $contact1 = $this->createContact('dnc-contact@test.com');
        $contact2 = $this->createContact('normal-contact@test.com');

        $this->addDncRecord($contact1->getId(), 'email');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=!dnc%3Aany');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringNotContainsString($contact1->getEmail(), $responseText, 'Contact with DNC should not appear in negative dnc:any search');
        $this->assertStringContainsString($contact2->getEmail(), $responseText, 'Contact without DNC should appear in negative dnc:any search');
    }

    public function testDncSearchWithMultipleChannelsOnSameContact(): void
    {
        $contact1 = $this->createContact('multi-dnc@test.com');
        $contact2 = $this->createContact('single-dnc@test.com');
        $contact3 = $this->createContact('no-dnc-multiple@test.com');

        $this->addDncRecord($contact1->getId(), 'email');
        $this->addDncRecord($contact1->getId(), 'sms');

        $this->addDncRecord($contact2->getId(), 'email');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Aany');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringContainsString($contact1->getEmail(), $responseText, 'Contact with multiple DNC channels should appear in dnc:any search');
        $this->assertStringContainsString($contact2->getEmail(), $responseText, 'Contact with single DNC channel should appear in dnc:any search');
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without DNC should not appear in dnc:any search');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Aemail');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringContainsString($contact1->getEmail(), $responseText, self::MESSAGE_EMAIL_DNC_SHOULD_APPEAR_IN_EMAIL_SEARCH);
        $this->assertStringContainsString($contact2->getEmail(), $responseText, self::MESSAGE_EMAIL_DNC_SHOULD_APPEAR_IN_EMAIL_SEARCH);
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without email DNC should not appear in dnc:email search');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search=dnc%3Asms');
        $this->assertResponseIsSuccessful();
        $responseText = $crawler->text();

        $this->assertStringContainsString($contact1->getEmail(), $responseText, 'Contact with SMS DNC should appear in dnc:sms search');
        $this->assertStringNotContainsString($contact2->getEmail(), $responseText, 'Contact without SMS DNC should not appear in dnc:sms search');
        $this->assertStringNotContainsString($contact3->getEmail(), $responseText, 'Contact without SMS DNC should not appear in dnc:sms search');
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function addDncRecord(int $contactId, string $channel): void
    {
        $this->em->getConnection()->executeStatement(
            'INSERT INTO '.MAUTIC_TABLE_PREFIX.'lead_donotcontact (lead_id, channel, reason, comments, date_added) VALUES (?, ?, ?, ?, ?)',
            [$contactId, $channel, 1, 'Test DNC', new \DateTime()],
            [\Doctrine\DBAL\Types\Types::INTEGER, \Doctrine\DBAL\Types\Types::STRING, \Doctrine\DBAL\Types\Types::INTEGER, \Doctrine\DBAL\Types\Types::STRING, \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE]
        );
    }
}
