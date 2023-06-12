<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class ResultControllerFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDownloadFileByFileNameAction(): void
    {
        $formModel    = self::$container->get('mautic.form.model.form');
        $formUploader = self::$container->get('mautic.form.helper.form_uploader');
        $fileName     = 'image.png';

        $this->createFile($fileName);

        $form  = new Form();
        $form->setAlias('apiform');
        $form->setName('API form');
        $form->setDescription('Test Form');
        $form->setFormType('standalone');
        $form->setIsPublished(true);

        $field = new Field();
        $field->setType('file');
        $field->setLabel('File');
        $field->setAlias('file_field');
        $field->setProperties([
            'allowed_file_size'       => 1,
            'allowed_file_extensions' => ['txt', 'jpg', 'gif', 'png'],
            'public'                  => true,
        ]);
        $field->setForm($form);
        $form->addField('file', $field);

        $formModel->saveEntity($form);

        $formId   = $form->getId();
        $fieldId  = $field->getId();

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

        unlink($fileName);
        unlink($formUploader->getCompleteFilePath($field, $fileName));
    }

    private function createFile(string $filename): void
    {
        $data = 'data:image/png;base64,AAAFBfj42Pj4';

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data              = base64_decode($data);

        file_put_contents($filename, $data);
    }
}
