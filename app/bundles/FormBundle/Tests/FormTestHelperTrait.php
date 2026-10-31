<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait FormTestHelperTrait
{
    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    protected function getPayLoad(array $payload = []): array
    {
        return [
            'name'        => $payload['name'] ?? 'Submission test form',
            'description' => $payload['description'] ?? 'Form created via submission test',
            'formType'    => $payload['type'] ?? 'standalone',
            'isPublished' => $payload['isPublished'] ?? true,
            'fields'      => $payload['fields'] ?? $this->getDefaultFields(),
        ];
    }

    /**
     * @return array<int,mixed>
     */
    protected function getDefaultFields(): array
    {
        return [
            [
                'label'     => 'Name',
                'type'      => 'text',
                'alias'     => 'name',
            ],
            [
                'label' => 'Submit',
                'type'  => 'button',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    protected function createFormViaApi(array $payload): array
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $form           = $response['form'];

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return $form;
    }

    /**
     * @param array<string,mixed> $form
     */
    protected function submitForm(array $form): void
    {
        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$form['id']}");
        $formCrawler = $crawler->filter('form[id=mauticform_submissiontestform]');
        $this->assertCount(1, $formCrawler);
        $form = $formCrawler->form();
        $form->setValues([
            'mauticform[name]' => 'Name',
        ]);
        $this->client->submit($form);

        $clientResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    protected function deleteAllFormResultsTable(): void
    {
        $conn = $this->em->getConnection();

        $sql  = "SELECT Table_name  from information_schema.tables where Table_name like '%form_results%' and table_schema in (SELECT DATABASE())";
        $stmt = $conn->prepare($sql);

        $tables = $stmt->executeQuery()->fetchAllAssociative();

        $sm = $conn->createSchemaManager();

        foreach ($tables as $table) {
            $sm->dropTable($table['Table_name']);
        }
    }
}
