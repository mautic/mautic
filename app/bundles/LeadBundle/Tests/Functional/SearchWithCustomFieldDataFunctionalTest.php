<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchWithCustomFieldDataFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @dataProvider dataTestCreatingCustomFieldIndexableAndSearchable
     */
    public function testCreatingCustomFieldIndexableAndSearchable(int $isIndex, string $expectedValue): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, 's/contacts/fields/new');
        $this->assertResponseIsSuccessful('Failed to load the form: '.$this->client->getResponse()->getContent());

        $form = $crawler->selectButton('Save')->form();

        $defaultValues = [
            'leadfield[label]'   => 'Custom field',
            'leadfield[alias]'   => 'custom_field',
            'leadfield[object]'  => 'lead',
            'leadfield[type]'    => 'text',
            'leadfield[group]'   => 'core',
            'leadfield[isIndex]' => $isIndex,
        ];

        $form->setValues($defaultValues);

        $this->client->submit($form);
        $formValuesUpdated = $form->getValues();

        $this->assertSame($expectedValue, $formValuesUpdated['leadfield[isIndex]'], 'Mismatch for field isIndex');
    }

    /**
     * @return iterable<string, array{0: int, 1: string}>
     */
    public function dataTestCreatingCustomFieldIndexableAndSearchable(): iterable
    {
        yield 'When "Add to Search Index" is enabled' => [1, '1'];

        yield 'When "Add to Search Index" is disabled' => [0, '0'];
    }

    public function testGlobalSearchForContactsUsingCustomFieldsData(): void
    {
        // Create a custom field for Contact
        $customFieldAlias = 'client_id';
        $this->createSearchableField($customFieldAlias, 'lead');

        // Create three contacts, one without custom field data.
        $contactData = [
            [
                'firstname'     => 'Contact',
                'lastname'      => 'One',
                'email'         => 'c@one.com',
                'company'       => 'One',
                'customFields'  => [$customFieldAlias => 'client_1'],
            ],
            [
                'firstname'     => 'Contact',
                'lastname'      => 'Two',
                'email'         => 'c@two.com',
                'company'       => 'Two',
                'customFields'  => [$customFieldAlias => 'client_2'],
            ],
            [
                'firstname' => 'Contact',
                'lastname'  => 'Three',
                'email'     => 'c@three.com',
                'company'   => 'Three',
            ],
        ];

        foreach ($contactData as $contactDatum) {
            $this->createContact($contactDatum);
        }

        // Search
        $response = $this->performSearch('/s/ajax?action=globalSearch&global_search=client&tmp=list');
        $content  = \json_decode($response->getContent(), true);
        $crawler  = new Crawler($content['newContent'], $this->client->getInternalRequest()->getUri());
        $this->assertSame('Contacts 2', $crawler->filterXPath('//div[@id="globalSearchPanel"]//div[contains(@class, "text-secondary")]')->text());

        $results = $crawler->filterXPath('//ul[contains(@class, "pa-0")]');
        $this->assertCount(2, $results->filter('li'));

        foreach ($results->filter('li')->each(fn ($li) => $li->filter('a')->eq(0)->html()) as $i => $result) {
            $this->assertStringContainsString($contactData[$i]['firstname'], $results->text());
            $this->assertStringContainsString($contactData[$i]['lastname'], $results->text());
        }
    }

    public function testSearchCompaniesWithCustomFields(): void
    {
        // Create a custom field for Company
        $customFieldAlias = 'client_id';
        $this->createSearchableField($customFieldAlias, 'company');

        // Create companies
        $this->createCompany([
            'name'          => 'ABC',
            'email'         => 'hello@abcexample.com',
            'customFields'  => [$customFieldAlias => 'client_id'],
        ]);

        $this->createCompany([
            'name'  => 'XYZ',
            'email' => 'hello@xyzexample.com',
        ]);

        // Search
        $response = $this->performSearch('/s/companies?search=client&tmp=list');
        $content  = \json_decode($response->getContent(), true);
        $crawler  = new Crawler($content['newContent'], $this->client->getInternalRequest()->getUri());

        $this->assertStringContainsString('ABC', $crawler->html());
        $this->assertStringNotContainsString('XYZ', $crawler->html());

        $translator = static::getContainer()->get('translator');

        $this->assertStringContainsString(
            $translator->trans('mautic.core.pagination.items', ['%count%' => 1]),
            $crawler->html()
        );

        $this->assertStringContainsString(
            $translator->trans('mautic.core.pagination.pages', ['%count%' => 1]),
            $crawler->html()
        );
    }

    private function createSearchableField(string $name, string $object): void
    {
        $field = new LeadField();
        $field->setName($name);
        $field->setAlias($name);
        $field->setObject($object);
        $field->setDateAdded(new \DateTime());
        $field->setDateAdded(new \DateTime());
        $field->setDateModified(new \DateTime());
        $field->setIsIndex(true);
        $field->setType('text');

        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
    }

    /**
     * @param array<string, string|array<string, string>> $data
     */
    private function createContact(array $data): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');

        $contact = (new Lead())
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setEmail($data['email'])
            ->setCompany($data['company']);

        foreach ($data['customFields'] ?? [] as $key => $value) {
            $contact->addUpdatedField($key, $value);
        }

        $leadModel->saveEntity($contact);
    }

    /**
     * @param array<string, string|array<string, string>> $data
     */
    private function createCompany(array $data): void
    {
        /** @var CompanyModel $companyModel */
        $companyModel = static::getContainer()->get('mautic.lead.model.company');

        $company = (new Company())
            ->setName($data['name'] ?? null)
            ->setEmail($data['email'] ?? null);

        foreach ($data['customFields'] ?? [] as $key => $value) {
            $company->addUpdatedField($key, $value);
        }

        $companyModel->saveEntity($company);

        $this->em->clear();
    }

    private function performSearch(string $url): Response
    {
        $this->client->xmlHttpRequest(Request::METHOD_GET, $url);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        return $response;
    }
}
