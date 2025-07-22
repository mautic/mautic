<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractSearchTest extends MauticMysqlTestCase
{
    /**
     * @param array<string, string|array<string, string>> $data
     */
    protected function createContact(array $data): void
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

    protected function createSearchableField(string $name, string $object): void
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
    protected function createCompany(array $data): void
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

    protected function performSearch(string $url): Response
    {
        $this->client->xmlHttpRequest(Request::METHOD_GET, $url);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        return $response;
    }
}
