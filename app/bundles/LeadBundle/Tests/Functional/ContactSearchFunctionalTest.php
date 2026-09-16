<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

final class ContactSearchFunctionalTest extends MauticMysqlTestCase
{
    public function testEmailSearchTreatsSqlWildcardsAsLiteralCharacters(): void
    {
        $literalEmail      = 'firstname_lastname@example.test';
        $wildcardMatchEmail = 'firstname-lastname@example.test';

        $this->createContact($literalEmail);
        $this->createContact($wildcardMatchEmail);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search='.urlencode('email:'.$literalEmail));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString($literalEmail, $crawler->text());
        self::assertStringNotContainsString($wildcardMatchEmail, $crawler->text());
    }

    private function createContact(string $email): void
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);
    }
}
