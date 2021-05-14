<?php

declare(strict_types=1);

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Validator;

use Mautic\CoreBundle\Exception\FileInvalidException;
use Mautic\CoreBundle\Validator\FileUploadValidator;
use Symfony\Component\Translation\TranslatorInterface;

class FileUploadValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Check that extension is valid
     */
    public function testValidExtension(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
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
     */
    public function testInvalidExtension(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
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
     */
    public function testFileSizeIsOk(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
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
     */
    public function testFileSizeIsBiggerThanAllowed(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
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
     */
    public function testBadExtensionAndBadSize(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->expects($this->exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Extension is not allowed', 'File size limit exceeded');

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
