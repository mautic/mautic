<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testLookupActionWithNoLookupFormField(): void
    {
        $this->makeRequest(['string' => 'Company']);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        Assert::assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), $clientResponse->getContent());
    }

    public function testLookupActionWithInvalidLookupFormField(): void
    {
        $this->makeRequest(['string' => 'Company', 'formId' => 3]);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        Assert::assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), $clientResponse->getContent());
    }

    public function testLookupActionWithTooFewLetters(): void
    {
        $form = $this->createForm();

        $this->makeRequest(['string' => 'Co', 'formId' => $form->getId()]);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $clientResponse->getContent());
        Assert::assertSame('{"error":"Invalid request param"}', $clientResponse->getContent(), $clientResponse->getContent());
    }

    public function testLookupActionWithCompanyData(): void
    {
        $this->createCompany('Unicorn A');
        $companyA = $this->createCompany('Company A');
        $companyB = $this->createCompany('Company B', 'Boston', 'Massachusetts');
        $form     = $this->createForm();

        $this->makeRequest(['search' => 'Company', 'formId' => $form->getId()]);
        $clientResponse = $this->client->getResponse();

        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        Assert::assertSame(
            [
                [
                    'id'           => (string) $companyA->getId(),
                    'companyname'  => 'Company A',
                    'companycity'  => null,
                    'companystate' => null,
                ], [
                    'id'           => (string) $companyB->getId(),
                    'companyname'  => 'Company B',
                    'companycity'  => 'Boston',
                    'companystate' => 'Massachusetts',
                ],
            ],
            json_decode($clientResponse->getContent(), true)
        );
    }

    /**
     * @param mixed[] $payload
     */
    private function makeRequest(array $payload): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/form/company-lookup/autocomplete',
            [],
            [],
            ['Content-Type' => 'application/json'],
            json_encode($payload)
        );
    }

    private function createCompany(string $name, string $city = null, string $state = null): Company
    {
        $company = new Company();
        $company->setName($name);
        $company->setCity($city);
        $company->setState($state);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    private function createForm(): Form
    {
        $field = new Field();
        $field->setAlias('company-lookup');
        $field->setLabel('Company');
        $field->setType('companyLookup');

        $form = new Form();
        $form->setName('Company Lookup Test');
        $form->setAlias('company-lookup-test');
        $form->addField(0, $field);
        $field->setForm($form);

        $this->em->persist($field);
        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }
}
