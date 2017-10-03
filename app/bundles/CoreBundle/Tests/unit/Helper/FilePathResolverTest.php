<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Exception\FilePathException;
use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FilePathResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Get correct name if few previous names are taken
     *
     * @covers \FilePathResolver::getUniqueFileName
     */
    public function testGetUniqueName()
    {
        $uploadDir     = 'my/upload/dir';
        $extension     = 'jpg';
        $dirtyFileName = 'fileName_x./-u'.$extension;

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->at(0))
            ->method('exists')
            ->with('my/upload/dir/filename_x.jpg')
            ->willReturn(true);

        $filesystemMock->expects($this->at(1))
            ->method('exists')
            ->with('my/upload/dir/filename_x-1.jpg')
            ->willReturn(true);

        $filesystemMock->expects($this->at(2))
            ->method('exists')
            ->with('my/upload/dir/filename_x-2.jpg')
            ->willReturn(false);

        $fileMock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileMock->expects($this->once())
            ->method('getClientOriginalName')
            ->with()
            ->willReturn($dirtyFileName);

        $fileMock->expects($this->once())
            ->method('getClientOriginalExtension')
            ->with()
            ->willReturn($extension);

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $name = $filePathResolver->getUniqueFileName($uploadDir, $fileMock);

        $this->assertSame('filename_x-2.jpg', $name);
    }

    /**
     * @testdox Throws an Exception if name cannot be generated
     *
     * @covers \FilePathResolver::getUniqueFileName
     */
    public function testCouldNotGetUniqueName()
    {
        $uploadDir     = 'my/upload/dir';
        $extension     = 'jpg';
        $dirtyFileName = 'fileName_x./-u'.$extension;

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->exactly(100))
            ->method('exists')
            ->willReturn(true);

        $fileMock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileMock->expects($this->once())
            ->method('getClientOriginalName')
            ->with()
            ->willReturn($dirtyFileName);

        $fileMock->expects($this->once())
            ->method('getClientOriginalExtension')
            ->with()
            ->willReturn($extension);

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $this->expectException(FilePathException::class);
        $this->expectExceptionMessage('Could not generate path');

        $filePathResolver->getUniqueFileName($uploadDir, $fileMock);
    }

    /**
     * @testdox No action is taken when directory already exists
     *
     * @covers \FilePathResolver::createDirectory
     */
    public function testNoActionIfDirectoryExists()
    {
        $directory = 'my/directory';

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(true);

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $filePathResolver->createDirectory($directory);
    }

    /**
     * @testdox Create new directory
     *
     * @covers \FilePathResolver::createDirectory
     */
    public function testCreateNewDirectory()
    {
        $directory = 'my/directory';

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(false);

        $filesystemMock->expects($this->once())
            ->method('mkdir')
            ->with($directory);

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $filePathResolver->createDirectory($directory);
    }

    /**
     * @testdox Directory could not be created
     *
     * @covers \FilePathResolver::createDirectory
     */
    public function testDirectoryCouldNotBeCreated()
    {
        $directory = 'my/directory';

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->once())
            ->method('exists')
            ->with($directory)
            ->willReturn(false);

        $filesystemMock->expects($this->once())
            ->method('mkdir')
            ->with($directory)
            ->willThrowException(new IOException(''));

        $this->expectException(FilePathException::class);
        $this->expectExceptionMessage('Could not create directory');

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $filePathResolver->createDirectory($directory);
    }

    /**
     * @testdox Successfuly detete file
     *
     * @covers \FilePathResolver::deleteFile
     */
    public function testDeleteFile()
    {
        $file = 'my/file';

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->once())
            ->method('remove')
            ->with($file);

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $filePathResolver->deleteFile($file);
    }

    /**
     * @testdox File could not be deleted
     *
     * @covers \FilePathResolver::deleteFile
     */
    public function testCouldNotDeleteFile()
    {
        $file = 'my/file';

        $inputHelper = new InputHelper();

        $filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMock->expects($this->once())
            ->method('remove')
            ->with($file)
            ->willThrowException(new IOException(''));

        $filePathResolver = new FilePathResolver($filesystemMock, $inputHelper);

        $filePathResolver->deleteFile($file);
    }
}
