<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\InputHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilePathResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Filesystem
     */
    private MockObject $filesystemMock;

    /**
     * @var MockObject|UploadedFile
     */
    private MockObject $fileMock;

    private InputHelper $inputHelper;

    private FilePathResolver $filePathResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystemMock   = $this->createMock(Filesystem::class);
        $this->fileMock         = $this->createMock(UploadedFile::class);
        $this->inputHelper      = new InputHelper();
        $this->filePathResolver = new FilePathResolver($this->filesystemMock, $this->inputHelper);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Get correct name if few previous names are taken')]
    public function testGetUniqueName(): void
    {
        $uploadDir     = 'my/upload/dir';
        $extension     = 'jpg';
        $dirtyFileName = 'fileName_x./-u'.$extension;
        $matcher       = $this->exactly(3);

        $this->filesystemMock->expects($matcher)
            ->method('exists')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('my/upload/dir/filename_x.jpg', $parameters[0]);

                    return true;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('my/upload/dir/filename_x-1.jpg', $parameters[0]);

                    return true;
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('my/upload/dir/filename_x-2.jpg', $parameters[0]);

                    return false;
                }
            });

        $this->fileMock->expects($this->once())
            ->method('getClientOriginalName')
            ->with()
            ->willReturn($dirtyFileName);

        $this->fileMock->expects($this->once())
            ->method('getClientOriginalExtension')
            ->with()
            ->willReturn($extension);

        $name = $this->filePathResolver->getUniqueFileName($uploadDir, $this->fileMock);

        $this->assertSame('filename_x-2.jpg', $name);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Throws an Exception if name cannot be generated')]
    public function testCouldNotGetUniqueName(): void
    {
        $uploadDir     = 'my/upload/dir';
        $extension     = 'jpg';
        $dirtyFileName = 'fileName_x./-u'.$extension;

        $this->filesystemMock->expects($this->exactly(1000))
            ->method('exists')
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('getClientOriginalName')
            ->with()
            ->willReturn($dirtyFileName);

        $this->fileMock->expects($this->once())
            ->method('getClientOriginalExtension')
            ->with()
            ->willReturn($extension);

        $this->expectException(FilePathException::class);
        $this->expectExceptionMessage('Could not generate path');

        $this->filePathResolver->getUniqueFileName($uploadDir, $this->fileMock);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('No action is taken when directory already exists')]
    public function testNoActionIfDirectoryExists(): void
    {
        $directory = 'my/directory';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(true);

        $this->filePathResolver->createDirectory($directory);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Create new directory')]
    public function testCreateNewDirectory(): void
    {
        $directory = 'my/directory';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(false);

        $this->filesystemMock->expects($this->once())
            ->method('mkdir')
            ->with($directory);

        $this->filePathResolver->createDirectory($directory);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Directory could not be created')]
    public function testDirectoryCouldNotBeCreated(): void
    {
        $directory = 'my/directory';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(false);

        $this->filesystemMock->expects($this->once())
            ->method('mkdir')
            ->with($directory)
            ->willThrowException(new IOException(''));

        $this->expectException(FilePathException::class);
        $this->expectExceptionMessage('Could not create directory');

        $this->filePathResolver->createDirectory($directory);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Successfuly detete file')]
    public function testDeleteFile(): void
    {
        $file = 'my/file';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($file)
            ->willReturn(true);

        $this->filesystemMock->expects($this->once())
            ->method('remove')
            ->with($file);

        $this->filePathResolver->delete($file);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('File could not be deleted')]
    public function testCouldNotDeleteFile(): void
    {
        $file = 'my/file';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($file)
            ->willReturn(true);

        $this->filesystemMock->expects($this->once())
            ->method('remove')
            ->with($file)
            ->willThrowException(new IOException(''));

        $this->filePathResolver->delete($file);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('File could not be deleted')]
    public function testDeleteFileWhichNotExists(): void
    {
        $file = 'my/file';

        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->with($file)
            ->willReturn(false);

        $this->filesystemMock->expects($this->never())
            ->method('remove');

        $this->filePathResolver->delete($file);
    }

    public function testMove(): void
    {
        $originalPath = 'my/file';
        $targetPath   = 'my/new/file';

        $this->filesystemMock->expects($this->once())
            ->method('rename')
            ->with($originalPath, $targetPath);

        $this->filePathResolver->move($originalPath, $targetPath);
    }
}
