<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\GrapesJsBuilderBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\PathsHelper;
use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class FileManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Mautic\CoreBundle\Helper\FileUploader
     */
    private $fileUploaderMock;

    /**
     * @var Mautic\CoreBundle\Helper\CoreParametersHelper
     */
    private $coreParametersHelperMock;

    /**
     * @var Mautic\CoreBundle\Helper\PathsHelper
     */
    private $pathsHelperMock;

    /**
     * @var MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager
     */
    private $fileManager;

    protected function setUp(): void
    {
        $this->fileUploader           = $this->createMock(FileUploader::class);
        $this->coreParametersHelper   = $this->createMock(CoreParametersHelper::class);
        $this->pathsHelper            = $this->createMock(PathsHelper::class);

        $this->fileManager = new FileManager($this->fileUploader, $this->coreParametersHelper, $this->pathsHelper);

        parent::setUp();
    }

    public function testUploadFilesToCDN()
    {
        $file = new UploadedFile('/path/to/photo.jpg', 'photo.jpg', 'image/jpeg', 123);
        $this->pathsHelper->method('getSystemPath')
        ->with('images')
        ->willReturn('media/images');
        $this->coreParametersHelper->method('get')->with('static_url')->willReturn('https://cdn');
        $this->assertEquals($this->fileManager->uploadFiles(new Request([], [], [], [], ['files' => [$file]])), ['https://cdn/media/images/']);
    }

    public function testUploadFilesToSite()
    {
        $file = new UploadedFile('/path/to/photo.jpg', 'photo.jpg', 'image/jpeg', 123);
        $this->pathsHelper->method('getSystemPath')
        ->with('images')
        ->willReturn('media/images');
        $this->coreParametersHelper->method('get')->with('static_url')->willReturn('https://site.com');
        $this->assertEquals($this->fileManager->uploadFiles(new Request([], [], [], [], ['files' => [$file]])), ['https://site.com/media/images/']);
    }

    public function testDeleteFile()
    {
        $file = 'media/images/test.jpg';

        $this->pathsHelper->method('getSystemPath')
        ->with('images')
        ->willReturn('media/images');

        $this->fileUploader->expects($this->once())
        ->method('delete')
        ->with($file);

        $this->fileManager->deleteFile('test.jpg');
    }

    public function testGetImages()
    {
        $this->pathsHelper->method('getSystemPath')
        ->with('images')
        ->willReturn('media/images');

        $this->coreParametersHelper->expects($this->any())
          ->method('get')
          ->will($this->returnValueMap(
              [
                  ['image_path_exclude', null, ['exclude_folder']],
                  ['static_url', null, 'https://site.com'],
              ]
        ));

        $this->assertContains(
            [
                'src'    => 'https://site.com/media/images/btn_GooglePlus.png',
                'width'  => 46,
                'type'   => 'image',
                'height' => 46,
              ], $this->fileManager->getImages(), "testArray doesn't contains value as value");
    }
}
