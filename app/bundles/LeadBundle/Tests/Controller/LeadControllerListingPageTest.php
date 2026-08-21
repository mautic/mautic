<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;

final class LeadControllerListingPageTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['contact_columns'] = 'testContactListingDecodesHtmlEntitiesInCompanySecondaryIdentifier' === $this->name()
            ? ['name', 'company', 'email']
            : ['name', 'location', 'email'];

        parent::setUp();
    }

    /**
     * @param string[] $location
     *
     * @throws ORMException
     */
    #[DataProvider('dataForContactListing')]
    public function testContactListingForLocation(array $location, string $expected): void
    {
        $this->createContact($location);

        $crawler    = $this->client->request('GET', 's/contacts');
        $rowContent = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr");

        $this->assertStringEndsWith($expected, $rowContent->text());
    }

    /**
     * @return iterable<string, array<int, string|string[]>>
     */
    public static function dataForContactListing(): iterable
    {
        yield 'With no location' => [
            // Location Details
            [
                'setCity'    => '',
                'setState'   => '',
                'setCountry' => '',
            ],
            // Expected suffice
            'John Doe john@doe.example.com',
        ];

        yield 'With whole location details' => [
            // Location Details
            [
                'setCity'    => 'Pune',
                'setState'   => 'MH',
                'setCountry' => 'India',
            ],
            // Expected suffice
            'John Doe Pune, MH john@doe.example.com',
        ];

        yield 'With only City for location' => [
            // Location Details
            [
                'setCity'    => 'Pune',
                'setState'   => '',
                'setCountry' => '',
            ],
            // Expected suffice
            'John Doe Pune john@doe.example.com',
        ];
    }

    public function testContactListingDecodesHtmlEntitiesInCompanyPrimaryIdentifier(): void
    {
        $contact = new Lead();
        $contact->setEmail('jane@doe.example.com');
        $contact->setCompany('Acme &amp; Sons');

        $this->em->persist($contact);
        $this->em->flush();

        $this->client->request('GET', 's/contacts');
        $content = (string) $this->client->getResponse()->getContent();

        Assert::assertStringContainsString('Acme &amp; Sons', $content);
        Assert::assertStringNotContainsString('Acme &amp;amp; Sons', $content);
    }

    public function testContactListingDecodesHtmlEntitiesInCompanySecondaryIdentifier(): void
    {
        $contact = new Lead();
        $contact->setFirstname('Jane');
        $contact->setLastname('Doe');
        $contact->setEmail('jane@doe.example.com');
        $contact->setCompany('Acme &amp; Sons');

        $this->em->persist($contact);
        $this->em->flush();

        $crawler        = $this->client->request('GET', 's/contacts');
        $nameColumnHtml = $crawler->filterXPath("//table[@id='leadTable']//tbody//tr/td[2]")->html();

        Assert::assertStringContainsString('Jane Doe', $nameColumnHtml);
        Assert::assertStringContainsString('Acme &amp; Sons', $nameColumnHtml);
        Assert::assertStringNotContainsString('Acme &amp;amp; Sons', $nameColumnHtml);
    }

    /**
     * @param string[] $location
     *
     * @throws ORMException
     */
    private function createContact(array $location = []): void
    {
        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $contact->setEmail('john@doe.example.com');

        foreach ($location as $name => $value) {
            if (empty($value)) {
                continue;
            }
            $contact->{$name}($value);
        }

        $this->em->persist($contact);
        $this->em->flush();
    }
}
