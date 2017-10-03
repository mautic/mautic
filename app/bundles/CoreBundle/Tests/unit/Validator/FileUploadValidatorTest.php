<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Check that extension is valid
     *
     * @covers \FileUploadValidator::checkExtension
     */
    public function testValidExtension()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->never())
            ->method('trans');

        $fileUploadValidator = new FileUploadValidator($translatorMock);

        $extension         = 'jpg';
        $allowedExtensions = [
            'jpg',
            'png',
        ];
        $extensionErrorMsg = 'My message';

        $fileUploadValidator->checkExtension($extension, $allowedExtensions, $extensionErrorMsg);
    }

    /**
     * @testdox Check that extension is not valid
     *
     * @covers \FileUploadValidator::checkExtension
     */
    public function testInvalidExtension()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->once())
            ->method('trans')
            ->willReturn('Extension is not allowed');

        $fileUploadValidator = new FileUploadValidator($translatorMock);

        $extension         = 'gif';
        $allowedExtensions = [
            'jpg',
            'png',
        ];
        $extensionErrorMsg = 'My message';

        $this->expectException(FileInvalidException::class);
        $this->expectExceptionMessage('Extension is not allowed');

        $fileUploadValidator->checkExtension($extension, $allowedExtensions, $extensionErrorMsg);
    }

    /**
     * @testdox Check file size is ok
     *
     * @covers \FileUploadValidator::checkFileSize
     */
    public function testFileSizeIsOk()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->never())
            ->method('trans');

        $fileUploadValidator = new FileUploadValidator($translatorMock);

        $fileSize        = 5242880; //5MB
        $maxUploadSizeMB = 6;
        $sizeErrorMsg    = 'My message';

        $fileUploadValidator->checkFileSize($fileSize, $maxUploadSizeMB, $sizeErrorMsg);
    }

    /**
     * @testdox Check file size bigger than allowed one
     *
     * @covers \FileUploadValidator::checkFileSize
     */
    public function testFileSizeIsBiggerThanAllowed()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->once())
            ->method('trans')
            ->willReturn('File size limit exceeded');

        $fileUploadValidator = new FileUploadValidator($translatorMock);

        $fileSize        = 5242880; //5MB
        $maxUploadSizeMB = 4;
        $sizeErrorMsg    = 'My message';

        $this->expectException(FileInvalidException::class);
        $this->expectExceptionMessage('File size limit exceeded');

        $fileUploadValidator->checkFileSize($fileSize, $maxUploadSizeMB, $sizeErrorMsg);
    }

    /**
     * @testdox Test concat message from validators
     *
     * @covers \FileUploadValidator::validate
     */
    public function testBadExtensionAndBadSize()
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->at(0))
            ->method('trans')
            ->willReturn('Extension is not allowed');

        $translatorMock->expects($this->at(1))
            ->method('trans')
            ->willReturn('File size limit exceeded');

        $fileUploadValidator = new FileUploadValidator($translatorMock);

        $extension         = 'gif';
        $allowedExtensions = [
            'jpg',
            'png',
        ];
        $extensionErrorMsg = 'My message';

        $fileSize        = 5242880; //5MB
        $maxUploadSizeMB = 4;
        $sizeErrorMsg    = 'My message';

        $this->expectException(FileInvalidException::class);
        $this->expectExceptionMessage('Extension is not allowed<br />File size limit exceeded');

        $fileUploadValidator->validate($fileSize, $extension, $maxUploadSizeMB, $allowedExtensions, $extensionErrorMsg, $sizeErrorMsg);
    }
}
