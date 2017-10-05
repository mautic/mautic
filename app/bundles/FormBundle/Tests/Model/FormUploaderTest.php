<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test;

use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\FormBundle\Crate\UploadFileCrate;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FormUploaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Uploader uploads files correctly
     *
     * @covers \FormUploader::uploadFiles
     */
    public function testSuccessfulUploadFiles()
    {
        $uploadDir = 'path/to/file';

        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->with('form_upload_dir')
            ->willReturn($uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $file1Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file2Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, 'file1');
        $filesToUpload->addFile($file2Mock, 'file2');

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $fileUploaderMock->expects($this->at(0))
            ->method('upload')
            ->with($uploadDir, $file1Mock)
            ->willReturn('upload1');

        $fileUploaderMock->expects($this->at(1))
            ->method('upload')
            ->with($uploadDir, $file2Mock)
            ->willReturn('upload2');

        $formUploader->uploadFiles($filesToUpload, $submission);

        $expected = [
            'key'   => 'value',
            'file1' => 'upload1',
            'file2' => 'upload2',
        ];

        $this->assertSame($expected, $submission->getResults());
    }

    /**
     * @testdox Uploader delete uploaded file if anz error occures
     *
     * @covers \FormUploader::uploadFiles
     */
    public function testUploadFilesWithError()
    {
        $uploadDir = 'path/to/file';

        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->with('form_upload_dir')
            ->willReturn($uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $file1Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file2Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesToUpload = new UploadFileCrate();
        $filesToUpload->addFile($file1Mock, 'file1');
        $filesToUpload->addFile($file2Mock, 'file2');

        $submission = new Submission();
        $submission->setResults(['key' => 'value']);

        $fileUploaderMock->expects($this->at(0))
            ->method('upload')
            ->with($uploadDir, $file1Mock)
            ->willReturn('upload1');

        $fileUploaderMock->expects($this->at(1))
            ->method('upload')
            ->with($uploadDir, $file2Mock)
            ->willThrowException(new FileUploadException());

        $fileUploaderMock->expects($this->once())
            ->method('deleteFile')
            ->with('path/to/file/upload1');

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('file2');

        $formUploader->uploadFiles($filesToUpload, $submission);

        $expected = [
            'key'   => 'value',
            'file1' => 'upload1',
            'file2' => 'upload2',
        ];

        $this->assertSame($expected, $submission->getResults());
    }

    /**
     * @testdox Uploader do nothing if no files for upload provided
     *
     * @covers \FormUploader::uploadFiles
     */
    public function testNoFilesUploadFiles()
    {
        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileUploaderMock->expects($this->never())
            ->method('upload');

        $fileUploaderMock->expects($this->never())
            ->method('deleteFile');

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
     * @covers \FormUploader::getCompleteFilePath
     */
    public function testGetCompleteFilePath()
    {
        $uploadDir = 'path/to/file';

        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->with('form_upload_dir')
            ->willReturn($uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $actual = $formUploader->getCompleteFilePath('fileName');

        $this->assertSame('path/to/file/fileName', $actual);
    }

    /**
     * @testdox Uploader delete files correctly
     *
     * @covers \FormUploader::deleteFileOfFormField
     */
    public function testDeleteFileOfFormField()
    {
        $uploadDir = 'path/to/file';

        $fileUploaderMock = $this->getMockBuilder(FileUploader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileUploaderMock->expects($this->once())
            ->method('deleteFile')
            ->with('path/to/file/fileName');

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
            ->with('form_upload_dir')
            ->willReturn($uploadDir);

        $formUploader = new FormUploader($fileUploaderMock, $coreParametersHelperMock);

        $submission = new Submission();
        $submission->setResults(['alias' => 'fileName']);

        $field = new Field();
        $field->setAlias('alias');
        $field->setType('file');

        $formUploader->deleteFileOfFormField($submission, $field);
    }
}
