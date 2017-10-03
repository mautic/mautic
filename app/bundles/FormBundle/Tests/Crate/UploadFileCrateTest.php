<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Test\Crate;

use Mautic\FormBundle\Crate\UploadFileCrate;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileCrateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Tests file size of Crate
     *
     * @covers \FilePathResolver::getUniqueFileName
     */
    public function testFileSize()
    {
        $uploadFileCrate = new UploadFileCrate();
        $this->assertFalse($uploadFileCrate->hasFiles());

        $file1Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file2Mock = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uploadFileCrate->addFile($file1Mock, 'file1');
        $uploadFileCrate->addFile($file2Mock, 'file2');

        $this->assertTrue($uploadFileCrate->hasFiles());
    }
}
