<?php

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
     *
     * @covers \Mautic\FormBundle\Helper\FormUploader::uploadFiles
     */
    public function testSuccessfulUploadFiles()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $file1Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file2Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form1Mock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $field1Mock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $form2Mock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId2);

        $field2Mock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $fileUploaderMock->expects($this->at(0))
            ->method('upload')
            ->with($path1, $file1Mock)
            ->willReturn('upload1.jpg');

        $fileUploaderMock->expects($this->at(1))
            ->method('upload')
            ->with($path2, $file2Mock)
            ->willReturn('upload2.txt');

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
     *
     * @covers \Mautic\FormBundle\Helper\FormUploader::uploadFiles
     */
    public function testUploadFilesWithError()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $file1Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file2Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form1Mock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form1Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $field1Mock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $form2Mock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form2Mock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId2);

        $field2Mock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $fileUploaderMock->expects($this->at(0))
            ->method('upload')
            ->with($path1, $file1Mock)
            ->willReturn('upload1.jpg');

        $fileUploaderMock->expects($this->at(1))
            ->method('upload')
            ->with($path2, $file2Mock)
            ->willThrowException(new FileUploadException());

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
     *
     * @covers \Mautic\FormBundle\Helper\FormUploader::uploadFiles
     */
    public function testNoFilesUploadFiles()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileUploaderMock->expects($this->never())
            ->method('upload');

        $fileUploaderMock->expects($this->never())
            ->method('delete');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $filesToUpload = new UploadFileCrate();
        $submission    = new Submission();

        $formUploader->uploadFiles($filesToUpload, $submission);
    }

    /**
     * @testdox Uploader returs correct path for file
     *
     * @covers \Mautic\FormBundle\Helper\FormUploader::getCompleteFilePath
     */
    public function testGetCompleteFilePath()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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
     *
     * @covers \Mautic\FormBundle\Helper\FormUploader::deleteAllFilesOfFormField
     */
    public function testDeleteAllFilesOfFormField()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileUploaderMock->expects($this->once())
            ->method('delete')
            ->with($this->uploadDir.'/1/fieldId1');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->once())
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function testDeleteFilesOfForm()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileUploaderMock
            ->method('delete')
            ->with($this->uploadDir.'/1');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->with('form_upload_dir')
            ->willReturn($this->uploadDir);

        $formMock = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formMock->expects($this->at(0))
            ->method('getId')
            ->with()
            ->willReturn($this->formId1);

        $formMock->expects($this->at(1))
            ->method('getId')
            ->with()
            ->willReturn(null);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);
        $formUploader->deleteFilesOfForm($formMock);

        $formMock->deletedId = $this->formId1;

        $formUploader->deleteFilesOfForm($formMock);
    }
}
