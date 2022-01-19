<?php

declare(strict_types=1);

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FormUploaderTest extends \PHPUnit\Framework\TestCase
{
    private $formId1   = 1;
    private $formId2   = 2;
    private $uploadDir = __DIR__.'/DummyFiles';

    /**
     * @testdox Uploader uploads files correctly
     */
    public function testSuccessfulUploadFiles(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $formUploader             = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $file1Mock                = $this->createMock(UploadedFile::class);
        $file2Mock                = $this->createMock(UploadedFile::class);
        $form1Mock                = $this->createMock(Form::class);
        $field1Mock               = $this->createMock(Field::class);
        $form2Mock                = $this->createMock(Form::class);
        $field2Mock               = $this->createMock(Field::class);

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $form1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $field1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId1');

        $field1Mock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($form1Mock);

        $field1Mock->expects($this->once())
            ->method('getAlias')
            ->with()
            ->willReturn('file1');

        $form2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId2);

        $field2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId2');

        $field2Mock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($form2Mock);

        $field2Mock->expects($this->once())
            ->method('getAlias')
            ->with()
            ->willReturn('file2');

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, $field1Mock);
        $filesToUpload->addFile($file2Mock, $field2Mock);

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $path1 = $this->uploadDir.'/1/fieldId1';
        $path2 = $this->uploadDir.'/2/fieldId2';

        $fileUploaderMock->expects($this->exactly(2))
            ->method('upload')
            ->withConsecutive([$path1, $file1Mock], [$path2, $file2Mock])
            ->willReturnOnConsecutiveCalls('upload1.jpg', 'upload2.txt');

        $formUploader->uploadFiles($filesToUpload, $submission);

        $expected = [
            'key'   => 'value',
            'file1' => 'upload1.jpg',
            'file2' => 'upload2.txt',
        ];

        $this->assertSame($expected, $submission->getResults());
    }

    /**
     * @testdox Uploader delete uploaded file if anz error occures
     */
    public function testUploadFilesWithError(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $formUploader             = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $file1Mock                = $this->createMock(UploadedFile::class);
        $file2Mock                = $this->createMock(UploadedFile::class);
        $form1Mock                = $this->createMock(Form::class);
        $field1Mock               = $this->createMock(Field::class);
        $form2Mock                = $this->createMock(Form::class);
        $field2Mock               = $this->createMock(Field::class);

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $form1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $field1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId1');

        $field1Mock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($form1Mock);

        $field1Mock->expects($this->once())
            ->method('getAlias')
            ->with()
            ->willReturn('file1');

        $form2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId2);

        $field2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId2');

        $field2Mock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($form2Mock);

        $field2Mock->expects($this->once())
            ->method('getAlias')
            ->with()
            ->willReturn('file2');

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, $field1Mock);
        $filesToUpload->addFile($file2Mock, $field2Mock);

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $path1 = $this->uploadDir.'/1/fieldId1';
        $path2 = $this->uploadDir.'/2/fieldId2';

        $fileUploaderMock->expects($this->exactly(2))
            ->method('upload')
            ->withConsecutive([$path1, $file1Mock], [$path2, $file2Mock])
            ->willReturnOnConsecutiveCalls('upload1.jpg', $this->throwException(new FileUploadException()));

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

    /**
     * @testdox Uploader do nothing if no files for upload provided
     */
    public function testNoFilesUploadFiles(): void
    {
        $fileUploaderMock = $this->createMock(FileUploader::class);

        $fileUploaderMock->expects($this->never())
            ->method('upload');

        $fileUploaderMock->expects($this->never())
            ->method('delete');

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $filesToUpload = new UploadFileCrate();
        $submission    = new Submission();

        $formUploader->uploadFiles($filesToUpload, $submission);
    }

    /**
     * @testdox Uploader returs correct path for file
     */
    public function testGetCompleteFilePath(): void
    {
        $fileUploaderMock = $this->createMock(FileUploader::class);

        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formMock = $this->createMock(Form::class);

        $formMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $fieldMock = $this->createMock(Field::class);

        $fieldMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId1');

        $fieldMock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($formMock);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $actual = $formUploader->getCompleteFilePath($fieldMock, 'fileName');

        $this->assertSame($this->uploadDir.'/1/fieldId1/fileName', $actual);
    }

    /**
     * @testdox Uploader delete files correctly
     */
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

        $formMock = $this->createMock(Form::class);

        $formMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $fieldMock = $this->createMock(Field::class);

        $fieldMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn('fieldId1');

        $fieldMock->expects($this->once())
            ->method('getForm')
            ->with()
            ->willReturn($formMock);

        $fieldMock->expects($this->once())
            ->method('isFileType')
            ->with()
            ->willReturn(true);

        $formUploader->deleteAllFilesOfFormField($fieldMock);
    }

    public function testDeleteFilesOfForm(): void
    {
        $fileUploaderMock         = $this->createMock(FileUploader::class);
        $formMock                 = $this->createMock(Form::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        $fileUploaderMock
            ->method('delete')
            ->with($this->uploadDir.'/1');

        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturnOnConsecutiveCalls($this->formId1, null);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $formUploader->deleteFilesOfForm($formMock);

        $formMock->deletedId = $this->formId1;

        $formUploader->deleteFilesOfForm($formMock);
    }
}
