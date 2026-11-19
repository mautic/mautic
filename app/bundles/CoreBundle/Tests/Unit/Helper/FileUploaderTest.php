<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Translation\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[CoversClass(FileUploader::class)]
final class FileUploaderTest extends \PHPUnit\Framework\TestCase
{
    #[TestDox('Uploader uploads files correctly')]
    public function testSuccessfulUpload(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

        $translatorMock = $this->createStub(Translator::class);

        $fileMock = $this->createMock(UploadedFile::class);

        $fileMock->expects($this->once())
            ->method('move')
            ->with($uploadDir, $fileName);

        $filePathResolverMock->expects($this->once())
            ->method('getUniqueFileName')
            ->with($uploadDir, $fileMock)
            ->willReturn($fileName);

        $filePathResolverMock->expects($this->once())
            ->method('createDirectory')
            ->with($uploadDir);

        $fileUploader = new FileUploader($filePathResolverMock, $translatorMock);

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[TestDox('Throw an Exception if Uploader could not create directory')]
    public function testCouldNotCreateDirectory(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

        $translatorMock = $this->createStub(Translator::class);

        $fileMock = $this->createMock(UploadedFile::class);

        $fileMock->expects($this->never())
            ->method('move');

        $filePathResolverMock->expects($this->once())
            ->method('getUniqueFileName')
            ->with($uploadDir, $fileMock)
            ->willReturn($fileName);

        $filePathResolverMock->expects($this->once())
            ->method('createDirectory')
            ->with($uploadDir)
            ->willThrowException(new FilePathException('Could not create directory'));

        $fileUploader = new FileUploader($filePathResolverMock, $translatorMock);

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Could not create directory');

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[TestDox('Throw an Exception if Uploader could not move file to givven directory')]
    public function testCouldNotMoveFile(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

        $translatorMock = $this->createMock(Translator::class);

        $translatorMock->method('trans')
            ->willReturn('Could not upload filed');

        $fileMock = $this->createMock(UploadedFile::class);

        $fileMock->expects($this->once())
            ->method('move')
            ->with($uploadDir, $fileName)
            ->willThrowException(new FileException());

        $filePathResolverMock->expects($this->once())
            ->method('getUniqueFileName')
            ->with($uploadDir, $fileMock)
            ->willReturn($fileName);

        $filePathResolverMock->expects($this->once())
            ->method('createDirectory')
            ->with($uploadDir);

        $fileUploader = new FileUploader($filePathResolverMock, $translatorMock);

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Could not upload file');

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[TestDox('Test for file delete')]
    public function testDeleteFile(): void
    {
        $file = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

        $translatorMock = $this->createStub(Translator::class);

        $filePathResolverMock->expects($this->once())
            ->method('delete')
            ->with($file);

        $fileUploader = new FileUploader($filePathResolverMock, $translatorMock);

        $fileUploader->delete($file);
    }
}
