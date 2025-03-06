<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SubmissionModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testSaveSubmissionChangeCompanyField(): void
    {
        [$formId, $formAlias] = $this->createFormWithCompanies();

        $this->submitFormWithCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe', 'Acquia', 'Keplerova');

        // Check the address.
        $companyRepository = $this->em->getRepository(Company::class);
        $companiesOriginal = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(1, $companiesOriginal);

        // Create contact with the same company but different address.
        $this->submitFormWithCompanies($formId, $formAlias, 'test2@acquia.cz', 'Luk', 'Syk', 'Acquia', 'Krejpskeho');

        // Check that the address is changed.
        $companiesOld = $companyRepository->findBy(['address1' => 'Keplerova']);
        Assert::assertCount(0, $companiesOld);
        $companiesNew = $companyRepository->findBy(['address1' => 'Krejpskeho']);
        Assert::assertCount(1, $companiesNew);
    }

    public function testSaveSubmissionChangeContactField(): void
    {
        [$formId, $formAlias] = $this->createFormWithoutCompanies();

        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Doe Smith');

        // Check the contact.
        $contactRepository = $this->em->getRepository(Lead::class);
        $contactsOriginal  = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(1, $contactsOriginal);

        // Create contact with the same email but different lastname.
        $this->submitFormWithoutCompanies($formId, $formAlias, 'test@acquia.cz', 'Luk', 'Sykora');

        // Check that the address is changed.
        $contactsOld = $contactRepository->findBy(['lastname' => 'Doe Smith']);
        Assert::assertCount(0, $contactsOld);
        $contactsNew = $contactRepository->findBy(['lastname' => 'Sykora']);
        Assert::assertCount(1, $contactsNew);
    }

    /**
     * @return mixed[]
     */
    private function createFormWithCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
                [
                    'label'        => 'Company',
                    'type'         => 'text',
                    'alias'        => 'companyname',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyname',
                ],
                [
                    'label'        => 'Company Address',
                    'type'         => 'text',
                    'alias'        => 'companyaddress1',
                    'mappedObject' => 'company',
                    'mappedField'  => 'companyaddress1',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @return mixed[]
     */
    private function createFormWithoutCompanies(): array
    {
        $payload = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First Name',
                    'type'         => 'text',
                    'alias'        => 'firstname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'firstname',
                ],
                [
                    'label'        => 'Last Name',
                    'type'         => 'text',
                    'alias'        => 'lastname',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'lastname',
                ],
            ],
        ];

        return $this->createForm($payload);
    }

    /**
     * @param mixed[] $payload
     *
     * @return array{int,string}
     */
    private function createForm(array $payload): array
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $formId         = $response['form']['id'];
        $formAlias      = $response['form']['alias'];
        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return [$formId, $formAlias];
    }

    private function submitFormWithCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname, string $company, string $companyAddress): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
            'mauticform[companyname]'     => $company,
            'mauticform[companyaddress1]' => $companyAddress,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    private function submitFormWithoutCompanies(int $formId, string $formAlias, string $email, string $firstname, string $lastname): void
    {
        $values = [
            'mauticform[email]'           => $email,
            'mauticform[firstname]'       => $firstname,
            'mauticform[lastname]'        => $lastname,
        ];
        $this->submitForm($formId, $formAlias, $values);
    }

    /**
     * @param array<string,string> $values
     */
    private function submitForm(int $formId, string $formAlias, array $values): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $this->assertResponseIsSuccessful();
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues($values);
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }
}
