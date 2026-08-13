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

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), (string) $clientResponse->getContent());

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

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), (string) $clientResponse->getContent());
    }

    protected function deleteAllFormResultsTable(): void
    {
        $conn = $this->em->getConnection();

        // Alias explicitly and read a single column: MySQL and MariaDB return
        // information_schema column names in different cases, so relying on the raw
        // "Table_name" key is engine-dependent (it is absent on MySQL).
        $sql  = "SELECT table_name AS name FROM information_schema.tables WHERE table_name LIKE '%form_results%' AND table_schema IN (SELECT DATABASE())";
        $stmt = $conn->prepare($sql);

        $tableNames = $stmt->executeQuery()->fetchFirstColumn();

        $sm = $conn->createSchemaManager();

        foreach ($tableNames as $tableName) {
            $sm->dropTable($tableName);
        }
    }
}
