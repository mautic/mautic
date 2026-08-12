<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Helper\FormUploader;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('database')]
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

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
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

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
        $clientResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($clientResponse->getContent(), true);
        $form     = $response['form'];
        $formId   = $form['id'];

        // Submit a form result (simulate a contact submission)
        $this->client->request(Request::METHOD_POST, "/form/{$formId}", [
            'mauticform[email]'  => 'test@example.com',
            'mauticform[formId]' => $formId,
            'mauticform[return]' => '',
        ]);
        $this->assertResponseIsSuccessful();

        // Call the addToSegmentAction
        $this->client->request(Request::METHOD_GET, "/s/forms/results/{$formId}/add-to-segment");
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

        $this->client->request(Request::METHOD_POST, '/api/forms/new', $formPayload);
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

    private function createFile(string $filename): void
    {
        $data = 'data:image/png;base64,AAAFBfj42Pj4';

        [, $data] = explode(';', $data);
        [, $data] = explode(',', $data);
        $data     = base64_decode($data);

        file_put_contents($filename, $data);
    }
}
