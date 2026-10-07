<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FieldModel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResultControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDownloadFileByFileNameAction(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel   = self::getContainer()->get(FieldModel::class);
        /** @var FormUploader $formUploader */
        $formUploader = self::getContainer()->get(FormUploader::class);
        $fileName     = 'image.png';

        $this->createFile($fileName);

        $formPayload  = [
            'name'        => 'API form',
            'formType'    => 'standalone',
            'alias'       => 'apiform',
            'description' => 'Test API Form',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'      => 'File',
                    'alias'      => 'file_field',
                    'type'       => 'file',
                    'properties' => [
                        'allowed_file_size'       => 1,
                        'allowed_file_extensions' => ['txt', 'jpg', 'gif', 'png'],
                        'public'                  => true,
                    ],
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($clientResponse->getContent(), true);
        $form     = $response['form'];
        $formId   = $form['id'];
        $fieldId  = $form['fields'][0]['id'];

        $crawler     = $this->client->request(Request::METHOD_GET, "/form/{$formId}");
        $formCrawler = $crawler->filter('form[id=mauticform_apiform]');
        $form        = $formCrawler->form();
        $file        = new UploadedFile($fileName, $fileName, 'image/png');
        $form->setValues([
            'mauticform[file_field]' => $file,
        ]);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->client->request(Request::METHOD_GET, "/forms/results/file/{$fieldId}/filename/{$fileName}");
        $this->assertResponseIsSuccessful();

        $field = $fieldModel->getEntity($fieldId);
        unlink($fileName);
        unlink($formUploader->getCompleteFilePath($field, $fileName));

        $folderPath = str_replace(DIRECTORY_SEPARATOR.$fileName, '', $formUploader->getCompleteFilePath($field, $fileName));
        if (is_dir($folderPath)) {
            rmdir($folderPath);
        }
    }

    public function testAddToSegmentActionRendersBatchForm(): void
    {
        // Create a form
        $formPayload = [
            'name'        => 'Segment Test Form',
            'formType'    => 'standalone',
            'alias'       => 'segmenttestform',
            'description' => 'Form for segment batch test',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'      => 'Email',
                    'alias'      => 'email',
                    'type'       => 'email',
                    'properties' => [],
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($clientResponse->getContent(), true);
        $form     = $response['form'];
        $formId   = $form['id'];

        // Submit a form result (simulate a contact submission)
        $this->client->request('POST', "/form/{$formId}", [
            'mauticform[email]'  => 'test@example.com',
            'mauticform[formId]' => $formId,
            'mauticform[return]' => '',
        ]);
        $this->assertResponseIsSuccessful();

        // Call the addToSegmentAction
        $this->client->request('GET', "/s/forms/results/{$formId}/add-to-segment");
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('form', (string) $response->getContent());
        $this->assertStringContainsString('batch', (string) $response->getContent());
    }

    public function testEditButtonIsDisplayedOnFormResultsPage(): void
    {
        $formPayload = [
            'name'        => 'Test Form for Results',
            'formType'    => 'standalone',
            'alias'       => 'testformresults',
            'description' => 'Test Form for Results Page',
            'isPublished' => true,
            'fields'      => [
                [
                    'label' => 'Name',
                    'alias' => 'name',
                    'type'  => 'text',
                ],
            ],
            'postAction'  => 'return',
        ];

        $this->client->request('POST', '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($clientResponse->getContent(), true);
        $form     = $response['form'];
        $formId   = $form['id'];
        $crawler  = $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}");
        self::assertResponseIsSuccessful();

        $editButton = $crawler->filter('a[href*="/s/forms/edit/'.$formId.'"]');
        $this->assertCount(1, $editButton, 'Edit button should be present on form results page');
    }

    public function testResultSortLinksTargetTheViewedForm(): void
    {
        // Two forms, so the one under test cannot be ID 1 - the ID the JS fallback
        // sorts by when no baseUrl reaches the template, which would let this pass
        // while still broken.
        $this->createFormViaApi('First sort form', 'firstsortform');
        $formId = $this->createFormViaApi('Second sort form', 'secondsortform');
        $this->assertGreaterThan(1, $formId);

        $crawler = $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}");
        $this->assertResponseIsSuccessful();

        $onClickHandlers = $crawler->filter('a.table-sort')->each(
            static fn (Crawler $sortLink): string => (string) $sortLink->attr('onclick')
        );
        $this->assertNotEmpty($onClickHandlers, 'Expected at least one sortable column header.');

        // The quotes pin this to the whole baseUrl argument, so a link pointing at
        // form 1 cannot satisfy it.
        $expectedBaseUrl = "'/s/forms/results/{$formId}'";

        foreach ($onClickHandlers as $onClickHandler) {
            $this->assertStringContainsString($expectedBaseUrl, $onClickHandler, 'Sort links must carry the viewed form ID so sorting cannot switch to another form.');
        }
    }

    private function createFormViaApi(string $name, string $alias): int
    {
        $this->client->request('POST', '/api/forms/new', [
            'name'        => $name,
            'formType'    => 'standalone',
            'alias'       => $alias,
            'isPublished' => true,
            'fields'      => [
                [
                    'label' => 'Email',
                    'alias' => 'email',
                    'type'  => 'email',
                ],
            ],
            'postAction'  => 'return',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return (int) json_decode((string) $this->client->getResponse()->getContent(), true)['form']['id'];
    }

    private function createFile(string $filename): void
    {
        $data = 'data:image/png;base64,AAAFBfj42Pj4';

        [, $data] = explode(';', $data);
        [, $data] = explode(',', $data);
        $data     = base64_decode($data);

        file_put_contents($filename, $data);
    }
}
