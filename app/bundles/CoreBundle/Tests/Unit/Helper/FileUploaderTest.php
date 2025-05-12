<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Exception\FileUploadException;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\FileUploader;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[\PHPUnit\Framework\Attributes\CoversClass(FileUploader::class)]
class FileUploaderTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Uploader uploads files correctly')]
    public function testSuccessfulUpload(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

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

        $fileUploader = new FileUploader($filePathResolverMock);

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Throw an Exception if Uploader could not create directory')]
    public function testCouldNotCreateDirectory(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

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

        $fileUploader = new FileUploader($filePathResolverMock);

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Could not create directory');

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Throw an Exception if Uploader could not move file to givven directory')]
    public function testCouldNotMoveFile(): void
    {
        $uploadDir = 'my/upload/dir';
        $fileName  = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

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

        $fileUploader = new FileUploader($filePathResolverMock);

        $this->expectException(FileUploadException::class);
        $this->expectExceptionMessage('Could not upload file');

        $fileUploader->upload($uploadDir, $fileMock);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test for file delete')]
    public function testDeleteFile(): void
    {
        $file = 'MyfileName';

        $filePathResolverMock = $this->createMock(FilePathResolver::class);

        $filePathResolverMock->expects($this->once())
            ->method('delete')
            ->with($file);

        $fileUploader = new FileUploader($filePathResolverMock);

        $fileUploader->delete($file);
    }
}
