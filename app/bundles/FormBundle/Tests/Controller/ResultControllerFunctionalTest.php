<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResultControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDownloadFileByFileNameAction(): void
    {
        $fieldModel   = static::getContainer()->get('mautic.form.model.field');
        $formUploader = static::getContainer()->get('mautic.form.helper.form_uploader');
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

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
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
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->request(Request::METHOD_GET, "/forms/results/file/{$fieldId}/filename/{$fileName}");
        $this->assertTrue($this->client->getResponse()->isOk());

        $field = $fieldModel->getEntity($fieldId);
        unlink($fileName);
        unlink($formUploader->getCompleteFilePath($field, $fileName));

        $folderPath = str_replace(DIRECTORY_SEPARATOR.$fileName, '', $formUploader->getCompleteFilePath($field, $fileName));
        if (is_dir($folderPath)) {
            rmdir($folderPath);
        }
    }

    private function createFile(string $filename): void
    {
        $data = 'data:image/png;base64,AAAFBfj42Pj4';

        [$type, $data]     = explode(';', $data);
        [, $data]          = explode(',', $data);
        $data              = base64_decode($data);

        file_put_contents($filename, $data);
    }
}
