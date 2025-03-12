<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Form\Validator;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class FileEncodingValidatorTest extends MauticMysqlTestCase
{
    public function testFileNONUTF8(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/contacts/import/new');
        $uploadForm = $crawler->selectButton('Upload')->form();
        $file       = new UploadedFile(__DIR__.'/../../../Fixtures/non_utf_8.csv', 'contacts.csv', 'itext/csv');

        $uploadForm['lead_import[file]']->setValue($file->getPathname());

        $crawler = $this->client->submit($uploadForm);

        Assert::assertStringContainsString('The file is not encoded correctly into UTF-8.', $crawler->html());
    }
}
