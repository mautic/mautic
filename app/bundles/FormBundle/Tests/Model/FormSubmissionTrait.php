<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait FormSubmissionTrait
{
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
     * @return mixed[]
     */
    private function createForm($payload): array
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
     * @param array<string, string|int|mixed> $values
     */
    private function submitForm(int $formId, string $formAlias, $values): void
    {
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_'.$formAlias.']');
        $this::assertCount(1, $formCrawler, $this->client->getResponse()->getContent());
        $form = $formCrawler->form();
        $form->setValues($values);
        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }
}
