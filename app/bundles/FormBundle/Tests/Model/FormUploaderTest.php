<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormUploader;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FormUploaderTest extends \PHPUnit\Framework\TestCase
{
    private int $formId1   = 1;

    private int $formId2   = 2;

    private string $uploadDir = __DIR__.'/DummyFiles';

    #[TestDox('Uploader uploads files correctly')]
    public function testSuccessfulUploadFiles(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $formUploader             = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $file1Mock                = $this->createStub(UploadedFile::class);
        $file2Mock                = $this->createStub(UploadedFile::class);
        $form1                    = new Form();
        ReflectionHelper::setEntityId($form1, $this->formId1);

        $field1 = new Field();
        ReflectionHelper::setEntityId($field1, 'fieldId1');
        $field1->setForm($form1);
        $field1->setAlias('file1');

        $form2 = new Form();
        ReflectionHelper::setEntityId($form2, $this->formId2);

        $field2 = new Field();
        ReflectionHelper::setEntityId($field2, 'fieldId2');
        $field2->setForm($form2);
        $field2->setAlias('file2');

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, $field1);
        $filesToUpload->addFile($file2Mock, $field2);

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $path1   = $this->uploadDir.'/1/fieldId1';
        $path2   = $this->uploadDir.'/2/fieldId2';
        $matcher = $this->exactly(2);

        $fileUploaderMock->expects($matcher)
            ->method('upload')->willReturnCallback(function (...$parameters) use ($matcher, $path1, $file1Mock, $path2, $file2Mock): string {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($path1, $parameters[0]);
                    $this->assertSame($file1Mock, $parameters[1]);

                    return 'upload1.jpg';
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($path2, $parameters[0]);
                    $this->assertSame($file2Mock, $parameters[1]);

                    return 'upload2.txt';
                }

                throw new Exception(sprintf('Method not be called for %dth time', $matcher->numberOfInvocations()));
            });

        $formUploader->uploadFiles($filesToUpload, $submission);

        $expected = [
            'key'   => 'value',
            'file1' => 'upload1.jpg',
            'file2' => 'upload2.txt',
        ];

        $this->assertSame($expected, $submission->getResults());
    }

    #[TestDox('Uploader delete uploaded file if anz error occures')]
    public function testUploadFilesWithError(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $formUploader             = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $file1Mock                = $this->createStub(UploadedFile::class);
        $file2Mock                = $this->createStub(UploadedFile::class);
        $form1                    = new Form();
        ReflectionHelper::setEntityId($form1, $this->formId1);

        $field1 = new Field();
        ReflectionHelper::setEntityId($field1, 'fieldId1');
        $field1->setForm($form1);
        $field1->setAlias('file1');

        $form2 = new Form();
        ReflectionHelper::setEntityId($form2, $this->formId2);

        $field2 = new Field();
        ReflectionHelper::setEntityId($field2, 'fieldId2');
        $field2->setForm($form2);
        $field2->setAlias('file2');

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, $field1);
        $filesToUpload->addFile($file2Mock, $field2);

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $path1   = $this->uploadDir.'/1/fieldId1';
        $path2   = $this->uploadDir.'/2/fieldId2';
        $matcher = $this->exactly(2);

        $fileUploaderMock->expects($matcher)
            ->method('upload')->willReturnCallback(function (...$parameters) use ($matcher, $path1, $file1Mock, $path2, $file2Mock) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($path1, $parameters[0]);
                    $this->assertSame($file1Mock, $parameters[1]);

                    return 'upload1.jpg';
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($path2, $parameters[0]);
                    $this->assertSame($file2Mock, $parameters[1]);
                    throw new FileUploadException();
                }
            });

        $fileUploaderMock->expects($this->once())
            ->method('delete')
            ->with($this->uploadDir.'/1/fieldId1/upload1.jpg');

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('file2');

        $formUploader->uploadFiles($filesToUpload, $submission);

        $expected = [
            'key'   => 'value',
            'file1' => 'upload1.jpg',
            'file2' => 'upload2.txt',
        ];

        $this->assertSame($expected, $submission->getResults());
    }

    #[TestDox('Uploader do nothing if no files for upload provided')]
    public function testNoFilesUploadFiles(): void
    {
        $fileUploaderMock = $this->createMock(FileUploader::class);

        $fileUploaderMock->expects($this->never())
            ->method('upload');

        $fileUploaderMock->expects($this->never())
            ->method('delete');

        $coreParametersHelperMock = $this->createStub(CoreParametersHelper::class);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $filesToUpload = new UploadFileCrate();
        $submission    = new Submission();

        $formUploader->uploadFiles($filesToUpload, $submission);
    }

    #[TestDox('Uploader returs correct path for file')]
    public function testGetCompleteFilePath(): void
    {
        $fileUploaderMock = $this->createStub(FileUploader::class);

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $form = new Form();
        ReflectionHelper::setEntityId($form, $this->formId1);

        $field = new Field();
        ReflectionHelper::setEntityId($field, 'fieldId1');
        $field->setForm($form);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $actual = $formUploader->getCompleteFilePath($field, 'fileName');

        $this->assertSame($this->uploadDir.'/1/fieldId1/fileName', $actual);
    }

    #[TestDox('Uploader delete files correctly')]
    public function testDeleteAllFilesOfFormField(): void
    {
        $fileUploaderMock = $this->createMock(FileUploader::class);

        $fileUploaderMock->expects($this->once())
            ->method('delete')
            ->with($this->uploadDir.'/1/fieldId1');

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $form = new Form();
        ReflectionHelper::setEntityId($form, $this->formId1);

        $field = new Field();
        ReflectionHelper::setEntityId($field, 'fieldId1');
        $field->setForm($form);
        $field->setType('file');

        $formUploader->deleteAllFilesOfFormField($field);
    }

    public function testDeleteFilesOfForm(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $form                     = new Form();
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $fileUploaderMock
            ->expects($this->exactly(2))
            ->method('delete')
            ->with($this->uploadDir.'/1');

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        ReflectionHelper::setEntityId($form, $this->formId1);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $formUploader->deleteFilesOfForm($form);

        // emulate Doctrine wiping the id after deletion, leaving only deletedId
        ReflectionHelper::setEntityId($form, null);
        $form->deletedId = $this->formId1;

        $formUploader->deleteFilesOfForm($form);
    }
}
